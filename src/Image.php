<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image;

use InvalidArgumentException;
use Joby\Smol\Image\Sizers\CoverSize;
use Joby\Smol\Image\Sizers\FitSize;
use Joby\Smol\Image\Sizers\HeightSize;
use Joby\Smol\Image\Sizers\OriginalSize;
use Joby\Smol\Image\Sizers\Sizer;
use Joby\Smol\Image\Sizers\WidthSize;
use RuntimeException;

/**
 * 
 * @phpstan-consistent-constructor
 */
class Image
{

    protected Size|null $source_size = null;

    /**
     * @internal Images should be constructed from SmolImage::load()
     */
    public function __construct(
        public readonly string $source,
        public readonly Sizer $sizer,
        public readonly Format $format,
        public readonly int $quality,
        public readonly int $blur = 0,
    ) {}

    /**
     * Size of this image's original source data.
     */
    public function sourceSize(): Size
    {
        return $this->source_size
            ??= $this->getSourceSize();
    }

    /**
     * Do the actual work of getting an image's original source data size.
     */
    protected function getSourceSize(): Size
    {
        $size = getimagesize($this->source);
        if ($size === false)
            throw new RuntimeException("Failed to get image size of {$this->source}");
        assert($size[0] > 0);
        assert($size[1] > 0);
        return new Size($size[0], $size[1]);
    }

    /**
     * Get the current size that will be output from this Image, with sizing rules applied.
     */
    public function size(): Size
    {
        return $this->sizer->finalSize($this->sourceSize());
    }

    /**
     * The size this image should be resized to, if applicable.
     * @internal
     */
    public function resize(): Size|null
    {
        return $this->sizer->resize($this->sourceSize());
    }

    /**
     * The size this image should be cropped to after resizing, if applicable.
     * @internal
     */
    public function crop(): Size|null
    {
        return $this->sizer->crop($this->sourceSize());
    }

    /**
     * Create a copy of this object that will render this image at its original size, with no blurring, as it is in the source file. Format selection remains.
     */
    public function original(): static
    {
        return $this
            ->withSizer(new OriginalSize)
            ->blur(0);
    }

    /**
     * Create an instance of this object that will render this image scaled and cropped to cover the given dimensions, including upscaling smaller images to cover the full given dimensions.
     * 
     * @param int<1,max> $width
     * @param int<1,max> $height
     */
    public function cover(int $width, int $height): static
    {
        return $this->withSizer(
            new CoverSize($width, $height),
        );
    }

    /**
     * Create an instance of this object that will render this image scaled down to fit within the given bounding box, including upscaling it to meet the bounding box dimensions if necessary.
     * 
     * You can optionally pass one dimension as null, and then the image will be scaled to exactly match the one that was provided.
     * 
     * @param int<1,max>|null $width
     * @param int<1,max>|null $height
     */
    public function fit(int|null $width, int|null $height): static
    {
        if ($width === null && $height === null)
            throw new InvalidArgumentException("You must provide at least one of \$height or \$width to fit an Image");
        elseif ($width === null)
            return $this->withSizer(
                new HeightSize($height),
            );
        else if ($height === null)
            return $this->withSizer(
                new WidthSize($width),
            );
        else
            return $this->withSizer(
                new FitSize($width, $height),
            );
    }

    /**
     * Create an instance of this object with the given Sizer instead of its current one.
     */
    public function withSizer(Sizer $sizer): static
    {
        return new static(
            $this->source,
            $sizer,
            $this->format,
            $this->quality,
        );
    }

    /**
     * Create an instance of this object with the jpeg output format.
     */
    public function jpeg(): static
    {
        return $this->withFormat(
            Format::jpeg,
        );
    }

    /**
     * Create an instance of this object with the webp output format.
     */
    public function webp(): static
    {
        return $this->withFormat(
            Format::webp,
        );
    }

    /**
     * Create an instance of this object with the png output format.
     */
    public function png(): static
    {
        return $this->withFormat(
            Format::png,
        );
    }

    /**
     * Blur this image to a level of intensity indicated by a 0-100 scale. Blurring technique and intensity may vary by driver.
     * @param int<0,100>|null $blur
     */
    public function blur(int|null $blur = 80): static
    {
        return new static(
            $this->source,
            $this->sizer,
            $this->format,
            $this->quality,
            $blur ?? 0,
        );
    }

    /**
     * Create an instance of this object with the given output format instead of its current one.
     */
    protected function withFormat(Format $format): static
    {
        return new static(
            $this->source,
            $this->sizer,
            $format,
            $this->quality,
        );
    }

    /**
     * Create an instance of this object with the given quality instead of its current one. Quality is on a scale of 0-100 and may be interpreted differently by different drivers and for different output formats (including being ignored entirely).
     */
    public function quality(int $quality): static
    {
        return new static(
            $this->source,
            $this->sizer,
            $this->format,
            $quality,
        );
    }

    /**
     * Save the result of this image's current settings to a given file.
     */
    public function save(string $path): void
    {
        SmolImage::driver()->save($this, $path);
    }

    /**
     * Output the result of this image's current settings to a string.
     */
    public function string(): string
    {
        return SmolImage::driver()->string($this);
    }

}
