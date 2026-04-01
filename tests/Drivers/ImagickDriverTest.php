<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image\Drivers;

use GdImage;
use Joby\Smol\Image\Image;
use Joby\Smol\Image\SmolImage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ImagickDriverTest extends TestCase
{

    protected static string $tmp;

    protected static string $square;    // 200x200 — committed fixture

    protected static string $landscape; // 200x100 — generated

    protected static string $portrait;  // 100x200 — generated

    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('imagick'))
            throw new RuntimeException('Imagick extension required for tests');
        // set up and clear out temp directory
        static::$tmp = __DIR__ . '/../output/ImagickDriverTest';
        mkdir(static::$tmp);
        foreach (glob(static::$tmp . '/*') as $file)
            unlink($file);
        // set up fixture files
        static::$square = __DIR__ . '/../fixtures/200x200.png';
        static::$landscape = __DIR__ . '/../fixtures/landscape.png';
        static::$portrait = __DIR__ . '/../fixtures/portrait.png';
        if (file_exists(static::$landscape))
            unlink(static::$landscape);
        if (file_exists(static::$portrait))
            unlink(static::$portrait);
        $gd = imagecreatefrompng(static::$square);
        if (!$gd)
            throw new RuntimeException("Failed to load test fixture");
        static::savePng(
            imagecrop($gd, ['x' => 0, 'y' => 50, 'width' => 200, 'height' => 100]),
            static::$landscape,
        );
        static::savePng(
            imagecrop($gd, ['x' => 50, 'y' => 0, 'width' => 100, 'height' => 200]),
            static::$portrait,
        );
        imagedestroy($gd);
    }

    protected function setUp(): void
    {
        if (!extension_loaded('imagick'))
            $this->markTestSkipped('Imagick extension not available');
        SmolImage::setDriver(new ImagickDriver());
    }

    protected static function savePng(GdImage|false $gd, string $path): void
    {
        if (!$gd)
            throw new RuntimeException("Failed to create test image");
        imagealphablending($gd, false);
        imagesavealpha($gd, true);
        imagepng($gd, $path);
        imagedestroy($gd);
    }

    protected function image(string $source): Image
    {
        return SmolImage::load($source);
    }

    protected function dimensions(string $data): array
    {
        $info = getimagesize('data://application/octet-stream;base64,' . base64_encode($data));
        if (!$info)
            throw new RuntimeException("Failed to get dimensions from output");
        return [$info[0], $info[1], $info[2]];
    }

    protected function outputPath(string $name): string
    {
        return static::$tmp . '/' . $name;
    }

    // --- original ---

    public function test_string_original_square(): void
    {
        [$w, $h] = $this->dimensions($this->image(static::$square)->string());
        $this->assertEquals(200, $w);
        $this->assertEquals(200, $h);
    }

    public function test_string_original_landscape(): void
    {
        [$w, $h] = $this->dimensions($this->image(static::$landscape)->string());
        $this->assertEquals(200, $w);
        $this->assertEquals(100, $h);
    }

    public function test_string_original_portrait(): void
    {
        [$w, $h] = $this->dimensions($this->image(static::$portrait)->string());
        $this->assertEquals(100, $w);
        $this->assertEquals(200, $h);
    }

    // --- save() ---

    public function test_save_produces_file_at_correct_dimensions(): void
    {
        $out = $this->outputPath('save_test.png');
        $this->image(static::$square)->cover(100, 100)->save($out);
        $this->assertFileExists($out);
        [$w, $h] = getimagesize($out);
        $this->assertEquals(100, $w);
        $this->assertEquals(100, $h);
    }

    // --- output formats ---

    public function test_outputs_webp(): void
    {
        $out = $this->outputPath('format_test.webp');
        $this->image(static::$square)->webp()->save($out);
        [, , $type] = getimagesize($out);
        $this->assertEquals(IMAGETYPE_WEBP, $type);
    }

    public function test_outputs_jpeg(): void
    {
        $out = $this->outputPath('format_test.jpg');
        $this->image(static::$square)->jpeg()->save($out);
        [, , $type] = getimagesize($out);
        $this->assertEquals(IMAGETYPE_JPEG, $type);
    }

    public function test_outputs_png(): void
    {
        $out = $this->outputPath('format_test.png');
        $this->image(static::$square)->png()->save($out);
        [, , $type] = getimagesize($out);
        $this->assertEquals(IMAGETYPE_PNG, $type);
    }

    // --- cover ---

    public function test_cover_square_source_produces_exact_dimensions(): void
    {
        $out = $this->outputPath('cover_square.png');
        $this->image(static::$square)->cover(150, 75)->save($out);
        [$w, $h] = getimagesize($out);
        $this->assertEquals(150, $w);
        $this->assertEquals(75, $h);
    }

    public function test_cover_landscape_source_produces_exact_dimensions(): void
    {
        $out = $this->outputPath('cover_landscape.png');
        $this->image(static::$landscape)->cover(150, 150)->save($out);
        [$w, $h] = getimagesize($out);
        $this->assertEquals(150, $w);
        $this->assertEquals(150, $h);
    }

    public function test_cover_portrait_source_produces_exact_dimensions(): void
    {
        $out = $this->outputPath('cover_portrait.png');
        $this->image(static::$portrait)->cover(150, 150)->save($out);
        [$w, $h] = getimagesize($out);
        $this->assertEquals(150, $w);
        $this->assertEquals(150, $h);
    }

    public function test_cover_upscales_small_target(): void
    {
        $out = $this->outputPath('cover_upscale.png');
        $this->image(static::$square)->cover(400, 400)->save($out);
        [$w, $h] = getimagesize($out);
        $this->assertEquals(400, $w);
        $this->assertEquals(400, $h);
    }

    // --- fit ---

    public function test_fit_landscape_source_within_square_target(): void
    {
        $out = $this->outputPath('fit_landscape.png');
        $this->image(static::$landscape)->fit(150, 150)->save($out);
        [$w, $h] = getimagesize($out);
        $this->assertEquals(150, $w);
        $this->assertEquals(75, $h);
    }

    public function test_fit_portrait_source_within_square_target(): void
    {
        $out = $this->outputPath('fit_portrait.png');
        $this->image(static::$portrait)->fit(150, 150)->save($out);
        [$w, $h] = getimagesize($out);
        $this->assertEquals(75, $w);
        $this->assertEquals(150, $h);
    }

    public function test_fit_never_exceeds_target_bounds(): void
    {
        foreach ([
            'square'    => static::$square,
            'landscape' => static::$landscape,
            'portrait'  => static::$portrait,
        ] as $name => $source) {
            $out = $this->outputPath("fit_bounds_{$name}.png");
            $this->image($source)->fit(150, 150)->save($out);
            [$w, $h] = getimagesize($out);
            $this->assertLessThanOrEqual(150, $w, "$name width exceeded target");
            $this->assertLessThanOrEqual(150, $h, "$name height exceeded target");
        }
    }

    public function test_fit_upscales(): void
    {
        $out = $this->outputPath('fit_upscale.png');
        $this->image(static::$square)->fit(400, 400)->save($out);
        [$w, $h] = getimagesize($out);
        $this->assertEquals(400, $w);
        $this->assertEquals(400, $h);
    }

    // --- blur ---

    public function test_blur_produces_valid_output(): void
    {
        $out = $this->outputPath('blur_default.png');
        $this->image(static::$square)->blur()->png()->save($out);
        $this->assertFileExists($out);
        [$w, $h] = getimagesize($out);
        $this->assertEquals(200, $w);
        $this->assertEquals(200, $h);
    }

    public function test_blur_does_not_affect_dimensions(): void
    {
        $out = $this->outputPath('blur_cover.png');
        $this->image(static::$square)->cover(150, 100)->blur()->png()->save($out);
        [$w, $h] = getimagesize($out);
        $this->assertEquals(150, $w);
        $this->assertEquals(100, $h);
    }

    public function test_blur_custom_intensity_produces_valid_output(): void
    {
        $out = $this->outputPath('blur_custom.png');
        $this->image(static::$square)->blur(20)->png()->save($out);
        $this->assertFileExists($out);
        [, , $type] = getimagesize($out);
        $this->assertEquals(IMAGETYPE_PNG, $type);
    }

}
