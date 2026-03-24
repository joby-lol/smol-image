<?php

namespace Joby\Smol\Image\Sizers;

use Joby\Smol\Image\Size;
use PHPUnit\Framework\TestCase;

class CoverSizeTest extends TestCase
{

    // --- target property ---

    public function test_target_is_stored_correctly(): void
    {
        $sizer = new CoverSize(800, 600);
        $this->assertEquals(800, $sizer->target->width);
        $this->assertEquals(600, $sizer->target->height);
    }

    // --- crop() ---

    public function test_crop_always_returns_target(): void
    {
        $sizer = new CoverSize(400, 300);
        $result = $sizer->crop(new Size(800, 600));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->width);
        $this->assertEquals(300, $result->height);
    }

    public function test_crop_returns_target_regardless_of_source_dimensions(): void
    {
        $sizer = new CoverSize(400, 300);
        foreach ([
            new Size(100, 100),
            new Size(1000, 1000),
            new Size(400, 300),
            new Size(200, 800),
            new Size(800, 200),
        ] as $source) {
            $result = $sizer->crop($source);
            $this->assertNotNull($result);
            $this->assertEquals(400, $result->width);
            $this->assertEquals(300, $result->height);
        }
    }

    // --- resize() --- landscape source, portrait/square target
    // target ratio < source ratio → constrain height, overflow width

    public function test_resize_landscape_source_square_target_constrains_height(): void
    {
        // source 800x600 (ratio 1.33), target 400x400 (ratio 1.0)
        // target ratio < source ratio → height = 400, width = 400 * (800/600) = 533
        $sizer = new CoverSize(400, 400);
        $result = $sizer->resize(new Size(800, 600));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->height);
        $this->assertEquals((int) round(400 * (800 / 600)), $result->width);
    }

    public function test_resize_landscape_source_portrait_target_constrains_height(): void
    {
        // source 1200x600 (ratio 2.0), target 200x400 (ratio 0.5)
        // target ratio < source ratio → height = 400, width = 400 * 2.0 = 800
        $sizer = new CoverSize(200, 400);
        $result = $sizer->resize(new Size(1200, 600));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->height);
        $this->assertEquals(800, $result->width);
    }

    // --- resize() --- portrait source, landscape/square target
    // target ratio > source ratio → constrain width, overflow height

    public function test_resize_portrait_source_square_target_constrains_width(): void
    {
        // source 600x800 (ratio 0.75), target 400x400 (ratio 1.0)
        // target ratio > source ratio → width = 400, height = 400 / 0.75 = 533
        $sizer = new CoverSize(400, 400);
        $result = $sizer->resize(new Size(600, 800));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->width);
        $this->assertEquals((int) round(400 / (600 / 800)), $result->height);
    }

    public function test_resize_portrait_source_landscape_target_constrains_width(): void
    {
        // source 400x800 (ratio 0.5), target 600x200 (ratio 3.0)
        // target ratio > source ratio → width = 600, height = 600 / 0.5 = 1200
        $sizer = new CoverSize(600, 200);
        $result = $sizer->resize(new Size(400, 800));
        $this->assertNotNull($result);
        $this->assertEquals(600, $result->width);
        $this->assertEquals(1200, $result->height);
    }

    // --- resize() --- square source

    public function test_resize_square_source_landscape_target_constrains_width(): void
    {
        // source 400x400 (ratio 1.0), target 800x400 (ratio 2.0)
        // target ratio > source ratio → width = 800, height = 800 / 1.0 = 800
        $sizer = new CoverSize(800, 400);
        $result = $sizer->resize(new Size(400, 400));
        $this->assertNotNull($result);
        $this->assertEquals(800, $result->width);
        $this->assertEquals(800, $result->height);
    }

    public function test_resize_square_source_portrait_target_constrains_height(): void
    {
        // source 400x400 (ratio 1.0), target 400x800 (ratio 0.5)
        // target ratio < source ratio → height = 800, width = 800 * 1.0 = 800
        $sizer = new CoverSize(400, 800);
        $result = $sizer->resize(new Size(400, 400));
        $this->assertNotNull($result);
        $this->assertEquals(800, $result->height);
        $this->assertEquals(800, $result->width);
    }

    // --- resize() --- matching ratios (equal ratios go to else branch)

    public function test_resize_matching_ratio_produces_exact_target(): void
    {
        // source 800x600, target 400x300 — same ratio, else branch
        $sizer = new CoverSize(400, 300);
        $result = $sizer->resize(new Size(800, 600));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->width);
        $this->assertEquals(300, $result->height);
    }

    // --- resize() --- upscaling

    public function test_resize_upscales_small_source(): void
    {
        // source 50x50 much smaller than target 800x600
        $sizer = new CoverSize(800, 600);
        $result = $sizer->resize(new Size(50, 50));
        $this->assertNotNull($result);
        $this->assertGreaterThanOrEqual(800, $result->width);
        $this->assertGreaterThanOrEqual(600, $result->height);
    }

    // --- critical invariant: resize output must fully cover target ---

    public function test_resize_output_always_covers_target_dimensions(): void
    {
        $sizer = new CoverSize(400, 300);
        foreach ([
            new Size(800, 600),
            new Size(600, 800),
            new Size(100, 100),
            new Size(1000, 200),
            new Size(200, 1000),
            new Size(400, 300),
            new Size(399, 299),
        ] as $source) {
            $result = $sizer->resize($source);
            $this->assertNotNull($result);
            $this->assertGreaterThanOrEqual(
                400,
                $result->width,
                "Resize width should cover target for source {$source->width}x{$source->height}",
            );
            $this->assertGreaterThanOrEqual(
                300,
                $result->height,
                "Resize height should cover target for source {$source->width}x{$source->height}",
            );
        }
    }

    // --- finalSize() always returns target ---

    public function test_final_size_always_returns_target(): void
    {
        $sizer = new CoverSize(400, 300);
        foreach ([
            new Size(800, 600),
            new Size(600, 800),
            new Size(400, 300),
            new Size(100, 100),
        ] as $source) {
            $result = $sizer->finalSize($source);
            $this->assertEquals(400, $result->width);
            $this->assertEquals(300, $result->height);
        }
    }

}
