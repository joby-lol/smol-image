<?php

namespace Joby\Smol\Image;

use Joby\Smol\Image\Drivers\GdDriver;
use Joby\Smol\Image\Sizers\CoverSize;
use Joby\Smol\Image\Sizers\FitSize;
use Joby\Smol\Image\Sizers\OriginalSize;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{

    protected static string $fixture = __DIR__ . '/fixtures/200x200.png';

    protected function setUp(): void
    {
        SmolImage::setDriver(new GdDriver());
        SmolImage::setFormat(Format::webp);
        SmolImage::setQuality(85);
    }

    protected function image(): Image
    {
        return SmolImage::load(static::$fixture);
    }

    // --- constructor / readonly properties ---

    public function test_source_is_stored(): void
    {
        $this->assertEquals(static::$fixture, $this->image()->source);
    }

    public function test_format_is_stored(): void
    {
        $this->assertEquals(Format::webp, $this->image()->format);
    }

    public function test_quality_is_stored(): void
    {
        $this->assertEquals(85, $this->image()->quality);
    }

    // --- sourceSize() ---

    public function test_source_size_returns_correct_dimensions(): void
    {
        $size = $this->image()->sourceSize();
        $this->assertEquals(200, $size->width);
        $this->assertEquals(200, $size->height);
    }

    public function test_source_size_is_lazily_cached(): void
    {
        $image = $this->image();
        $first = $image->sourceSize();
        $second = $image->sourceSize();
        $this->assertSame($first, $second);
    }

    // --- size() ---

    public function test_size_returns_source_size_for_original(): void
    {
        $size = $this->image()->size();
        $this->assertEquals(200, $size->width);
        $this->assertEquals(200, $size->height);
    }

    public function test_size_returns_cover_dimensions(): void
    {
        $size = $this->image()->cover(100, 50)->size();
        $this->assertEquals(100, $size->width);
        $this->assertEquals(50, $size->height);
    }

    public function test_size_returns_fit_dimensions(): void
    {
        $size = $this->image()->fit(100, 100)->size();
        $this->assertEquals(100, $size->width);
        $this->assertEquals(100, $size->height);
    }

    // --- resize() / crop() ---

    public function test_resize_returns_null_for_original(): void
    {
        $this->assertNull($this->image()->resize());
    }

    public function test_crop_returns_null_for_original(): void
    {
        $this->assertNull($this->image()->crop());
    }

    public function test_resize_returns_null_for_fit(): void
    {
        // fit only resizes, never crops
        $this->assertNull($this->image()->fit(100, 100)->crop());
    }

    public function test_crop_returns_target_for_cover(): void
    {
        $crop = $this->image()->cover(100, 50)->crop();
        $this->assertNotNull($crop);
        $this->assertEquals(100, $crop->width);
        $this->assertEquals(50, $crop->height);
    }

    // --- immutability ---

    public function test_cover_returns_new_instance(): void
    {
        $original = $this->image();
        $covered = $original->cover(100, 100);
        $this->assertNotSame($original, $covered);
    }

    public function test_fit_returns_new_instance(): void
    {
        $original = $this->image();
        $fitted = $original->fit(100, 100);
        $this->assertNotSame($original, $fitted);
    }

    public function test_original_returns_new_instance(): void
    {
        $covered = $this->image()->cover(100, 100);
        $original = $covered->original();
        $this->assertNotSame($covered, $original);
    }

    public function test_jpeg_returns_new_instance(): void
    {
        $image = $this->image();
        $this->assertNotSame($image, $image->jpeg());
    }

    public function test_webp_returns_new_instance(): void
    {
        $image = $this->image();
        $this->assertNotSame($image, $image->webp());
    }

    public function test_png_returns_new_instance(): void
    {
        $image = $this->image();
        $this->assertNotSame($image, $image->png());
    }

    public function test_quality_returns_new_instance(): void
    {
        $image = $this->image();
        $this->assertNotSame($image, $image->quality(50));
    }

    // --- immutability preserves other properties ---

    public function test_cover_preserves_format(): void
    {
        $image = $this->image()->jpeg()->cover(100, 100);
        $this->assertEquals(Format::jpeg, $image->format);
    }

    public function test_cover_preserves_quality(): void
    {
        $image = $this->image()->quality(50)->cover(100, 100);
        $this->assertEquals(50, $image->quality);
    }

    public function test_jpeg_preserves_source(): void
    {
        $this->assertEquals(static::$fixture, $this->image()->jpeg()->source);
    }

    public function test_quality_preserves_format(): void
    {
        $image = $this->image()->jpeg()->quality(50);
        $this->assertEquals(Format::jpeg, $image->format);
    }

    public function test_quality_preserves_source(): void
    {
        $this->assertEquals(static::$fixture, $this->image()->quality(50)->source);
    }

    // --- format methods ---

    public function test_jpeg_sets_format(): void
    {
        $this->assertEquals(Format::jpeg, $this->image()->jpeg()->format);
    }

    public function test_webp_sets_format(): void
    {
        $this->assertEquals(Format::webp, $this->image()->webp()->format);
    }

    public function test_png_sets_format(): void
    {
        $this->assertEquals(Format::png, $this->image()->png()->format);
    }

    // --- quality ---

    public function test_quality_sets_value(): void
    {
        $this->assertEquals(42, $this->image()->quality(42)->quality);
    }

    // --- original() restores original size ---

    public function test_original_restores_original_size(): void
    {
        $size = $this->image()->cover(100, 50)->original()->size();
        $this->assertEquals(200, $size->width);
        $this->assertEquals(200, $size->height);
    }

    // --- withSizer() ---

    public function test_with_sizer_returns_new_instance(): void
    {
        $image = $this->image();
        $this->assertNotSame($image, $image->withSizer(new OriginalSize()));
    }

    public function test_with_sizer_replaces_sizer(): void
    {
        $image = $this->image()->cover(100, 100)->withSizer(new FitSize(50, 50));
        $this->assertEquals(50, $image->size()->width);
        $this->assertEquals(50, $image->size()->height);
    }

    // --- save() / string() delegate to driver ---

    public function test_string_returns_string(): void
    {
        $this->assertIsString($this->image()->string());
    }

    public function test_save_produces_file(): void
    {
        $out = __DIR__ . '/output/image_test_save.png';
        $this->image()->png()->save($out);
        $this->assertFileExists($out);
    }

    public function test_save_respects_format(): void
    {
        $out = __DIR__ . '/output/image_test_jpeg.jpg';
        $this->image()->jpeg()->save($out);
        [, , $type] = getimagesize($out);
        $this->assertEquals(IMAGETYPE_JPEG, $type);
    }

}
