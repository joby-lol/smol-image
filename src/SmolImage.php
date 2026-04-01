<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image;

use InvalidArgumentException;
use Joby\Smol\Image\Drivers\GdDriver;
use Joby\Smol\Image\Sizers\OriginalSize;

class SmolImage
{

    protected static DriverInterface|null $driver = null;

    protected static Format $format = Format::webp;

    protected static int $quality = 85;

    /**
     * Set the driver to use for transforming images. Default is GdDriver, which is not very memory efficient, but is widely available in most environments.
     */
    public static function setDriver(DriverInterface $driver): void
    {
        static::$driver = $driver;
    }

    /**
     * Set the default output format for images. Default is webp, which is reasonable for most use cases.
     */
    public static function setFormat(Format $format): void
    {
        static::$format = $format;
    }

    /**
     * Set the default quality for output images. Default is 85, which is reasonable for most use cases.
     */
    public static function setQuality(int $quality): void
    {
        static::$quality = $quality;
    }

    /**
     * Summary of driver
     * @return DriverInterface|GdDriver
     */
    public static function driver(): DriverInterface
    {
        return static::$driver
            ??= new GdDriver;
    }

    public static function load(string $source): Image
    {
        if (!file_exists($source))
            throw new InvalidArgumentException("Image not found: $source");
        return new Image(
            $source,
            new OriginalSize(),
            static::$format,
            static::$quality,
            0,
        );
    }

}
