<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image\Drivers;

use GdImage;
use Joby\Smol\Image\DriverInterface;
use Joby\Smol\Image\Format;
use Joby\Smol\Image\Image;
use RuntimeException;

class GdDriver implements DriverInterface
{

    public function save(Image $image, string $path): void
    {
        $gd = $this->size($this->getImageObject($image->source), $image);
        $result = match ($image->format) {
            Format::jpeg => imagejpeg($gd, $path, $image->quality),
            Format::webp => imagewebp($gd, $path, $image->quality),
            Format::png  => imagepng($gd, $path, (int) round(9 - ($image->quality / 100 * 9))),
        };
        if (!$result)
            throw new RuntimeException("Failed to save image to $path");
    }

    public function string(Image $image): string
    {
        $gd = $this->size($this->getImageObject($image->source), $image);
        ob_start();
        $result = match ($image->format) {
            Format::jpeg => imagejpeg($gd, null, $image->quality),
            Format::webp => imagewebp($gd, null, $image->quality),
            Format::png  => imagepng($gd, null, (int) round(9 - ($image->quality / 100 * 9))),
        };
        $output = ob_get_clean();
        if (!$result || $output === false)
            throw new RuntimeException("Failed to render image to string");
        return $output;
    }

    protected function getImageObject(string $source): GdImage
    {
        $result = match (strtolower(pathinfo($source, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => imagecreatefromjpeg($source),
            'png'         => imagecreatefrompng($source),
            'gif'         => imagecreatefromgif($source),
            'webp'        => imagecreatefromwebp($source),
            default       => imagecreatefromstring(
                file_get_contents($source)
                ?: throw new RuntimeException("Failed to open $source for creating image object")
            ),
        };
        if ($result instanceof GdImage)
            return $result;
        else
            throw new RuntimeException("Failed to open $source with GD");
    }

    protected function size(GdImage $gd_image, Image $image): GdImage
    {
        // do resize operation
        if ($resize = $image->resize()) {
            $resized_gd_image = $this->canvas($resize->width, $resize->height);
            imagecopyresampled(
                $resized_gd_image,
                $gd_image,
                0,
                0,
                0,
                0,
                $resize->width,
                $resize->height,
                $image->sourceSize()->width,
                $image->sourceSize()->height,
            );
            $gd_image = $resized_gd_image;
        }
        // do crop operation
        if ($crop = $image->crop()) {
            $cropped_gd_image = $this->canvas($crop->width, $crop->height);
            $base = $resize ?? $image->sourceSize();
            imagecopyresampled(
                $cropped_gd_image,
                $gd_image,
                0,
                0,
                intval(($base->width - $crop->width) / 2),
                intval(($base->height - $crop->height) / 2),
                $crop->width,
                $crop->height,
                $crop->width,
                $crop->height,
            );
            $gd_image = $cropped_gd_image;
        }
        // return result
        return $gd_image;
    }

    /**
     * @param int<1,max> $width
     * @param int<1,max> $height
     */
    protected function canvas(int $width, int $height): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        if (!$canvas)
            throw new RuntimeException("Error creating GD canvas");
        return $canvas;
    }

}
