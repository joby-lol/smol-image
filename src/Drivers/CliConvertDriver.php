<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image\Drivers;

use Joby\Smol\Image\DriverInterface;
use Joby\Smol\Image\Format;
use Joby\Smol\Image\Image;
use RuntimeException;

class CliConvertDriver implements DriverInterface
{

    public function __construct(
        protected readonly string $executable = 'convert',
    ) {}

    public function save(Image $image, string $path): void
    {
        $command = $this->buildCommand($image, $path);
        exec($command, $output, $return);
        if ($return !== 0)
            throw new RuntimeException("ImageMagick convert failed (exit $return): $command");
    }

    public function string(Image $image): string
    {
        $tmp = sys_get_temp_dir() . '/' . uniqid('smol-image-') . '.' . $this->extension($image->format);
        try {
            $this->save($image, $tmp);
            $result = file_get_contents($tmp);
            if ($result === false)
                throw new RuntimeException("Failed to read temp file: $tmp");
            return $result;
        }
        finally {
            if (file_exists($tmp))
                unlink($tmp);
        }
    }

    protected function buildCommand(Image $image, string $outputPath): string
    {
        $parts = [
            escapeshellarg($this->executable),
            escapeshellarg($image->source),
        ];

        // resize — use ! to force exact dimensions since sizer has already done the math
        if ($resize = $image->resize()) {
            $parts[] = '-resize ' . escapeshellarg($resize->width . 'x' . $resize->height . '!');
        }

        // crop — center gravity + extent
        if ($crop = $image->crop()) {
            $parts[] = '-gravity center';
            $parts[] = '-extent ' . escapeshellarg($crop->width . 'x' . $crop->height);
        }

        // blur
        if ($image->blur) {
            $sigma = $image->blur / 100 * 20;
            $parts[] = '-blur ' . escapeshellarg("0x$sigma");
        }

        // set quality
        $parts[] = '-quality ' . $image->quality;

        // prefix output path with format to ensure ImageMagick uses the right encoder
        // regardless of whatever extension the caller used
        $parts[] = escapeshellarg($this->formatPrefix($image->format) . $outputPath);

        return implode(' ', $parts);
    }

    protected function formatPrefix(Format $format): string
    {
        return match ($format) {
            Format::jpeg => 'jpg:',
            Format::webp => 'webp:',
            Format::png  => 'png:',
        };
    }

    protected function extension(Format $format): string
    {
        return match ($format) {
            Format::jpeg => 'jpg',
            Format::webp => 'webp',
            Format::png  => 'png',
        };
    }

}
