<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image\Drivers;

use Imagick;
use Joby\Smol\Image\DriverInterface;
use Joby\Smol\Image\Format;
use Joby\Smol\Image\Image;
use RuntimeException;

class ImagickDriver implements DriverInterface
{

    public function save(Image $image, string $path): void
    {
        $imagick = $this->transform($this->getImageObject($image->source), $image);
        $imagick->setImageFormat($this->formatString($image->format));
        $imagick->setImageCompressionQuality($image->quality);
        if (!$imagick->writeImage($path))
            throw new RuntimeException("Failed to save image to $path");
    }

    public function string(Image $image): string
    {
        $imagick = $this->transform($this->getImageObject($image->source), $image);
        $imagick->setImageFormat($this->formatString($image->format));
        $imagick->setImageCompressionQuality($image->quality);
        return $imagick->getImageBlob();
    }

    protected function getImageObject(string $source): Imagick
    {
        $imagick = new Imagick();
        if (!$imagick->readImage($source))
            throw new RuntimeException("Failed to open $source with Imagick");
        return $imagick;
    }

    protected function transform(Imagick $imagick, Image $image): Imagick
    {
        // do resize operation
        if ($resize = $image->resize()) {
            $imagick->resizeImage(
                $resize->width,
                $resize->height,
                Imagick::FILTER_LANCZOS,
                1,
            );
        }
        // do crop operation
        if ($crop = $image->crop()) {
            $base = $resize ?? $image->sourceSize();
            $imagick->cropImage(
                $crop->width,
                $crop->height,
                (int) (($base->width - $crop->width) / 2),
                (int) (($base->height - $crop->height) / 2),
            );
            $imagick->setImagePage(0, 0, 0, 0);
        }
        // do blur operation
        if ($image->blur) {
            $sigma = $image->blur / 100 * 20;
            $imagick->gaussianBlurImage(0, $sigma);
        }
        // return transformed Imagick object
        return $imagick;
    }

    protected function formatString(Format $format): string
    {
        return match ($format) {
            Format::jpeg => 'JPEG',
            Format::webp => 'WEBP',
            Format::png  => 'PNG',
        };
    }

}
