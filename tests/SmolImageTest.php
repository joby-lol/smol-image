<?php

namespace Joby\Smol\Image;

use InvalidArgumentException;
use Joby\Smol\Image\Drivers\GdDriver;
use Joby\Smol\Image\Sizers\OriginalSize;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SmolImageTest extends TestCase
{

    protected static string $fixture = __DIR__ . '/fixtures/200x200.png';

    protected function setUp(): void
    {
        // reset static state to defaults before each test
        SmolImage::setFormat(Format::webp);
        SmolImage::setQuality(85);
        SmolImage::setDriver(new GdDriver());
    }

    // --- driver() ---

    public function test_driver_returns_gd_driver_by_default(): void
    {
        // unset the driver to test lazy default
        $ref = new ReflectionClass(SmolImage::class);
        $prop = $ref->getProperty('driver');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        $this->assertInstanceOf(GdDriver::class, SmolImage::driver());
    }

    public function test_set_driver_replaces_driver(): void
    {
        $mock = $this->createMock(DriverInterface::class);
        SmolImage::setDriver($mock);
        $this->assertSame($mock, SmolImage::driver());
    }

    // --- setFormat() / setQuality() ---

    public function test_set_format_affects_loaded_image(): void
    {
        SmolImage::setFormat(Format::jpeg);
        $image = SmolImage::load(static::$fixture);
        $this->assertEquals(Format::jpeg, $image->format);
    }

    public function test_set_quality_affects_loaded_image(): void
    {
        SmolImage::setQuality(42);
        $image = SmolImage::load(static::$fixture);
        $this->assertEquals(42, $image->quality);
    }

    public function test_default_format_is_webp(): void
    {
        $image = SmolImage::load(static::$fixture);
        $this->assertEquals(Format::webp, $image->format);
    }

    public function test_default_quality_is_85(): void
    {
        $image = SmolImage::load(static::$fixture);
        $this->assertEquals(85, $image->quality);
    }

    // --- load() ---

    public function test_load_returns_image(): void
    {
        $image = SmolImage::load(static::$fixture);
        $this->assertInstanceOf(Image::class, $image);
    }

    public function test_load_stores_source_path(): void
    {
        $image = SmolImage::load(static::$fixture);
        $this->assertEquals(static::$fixture, $image->source);
    }

    public function test_load_throws_for_missing_file(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SmolImage::load('/nonexistent/path/image.png');
    }

    public function test_load_throws_message_contains_path(): void
    {
        $path = '/nonexistent/path/image.png';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote($path, '/') . '/');
        SmolImage::load($path);
    }

}
