<?php

namespace Joby\Smol\Image\Sizers;

use Joby\Smol\Image\Size;
use PHPUnit\Framework\TestCase;

class FitSizeTest extends TestCase
{

    // --- target property ---

    public function test_target_is_stored_correctly(): void
    {
        $sizer = new FitSize(800, 600);
        $this->assertEquals(800, $sizer->target->width);
        $this->assertEquals(600, $sizer->target->height);
    }

    // --- crop() ---

    public function test_crop_always_returns_null(): void
    {
        $sizer = new FitSize(400, 300);
        foreach ([
            new Size(800, 600),
            new Size(100, 100),
            new Size(400, 300),
            new Size(200, 800),
        ] as $source) {
            $this->assertNull($sizer->crop($source));
        }
    }

    // --- resize() --- landscape source, portrait/square target
    // target ratio > source ratio → constrain height, letterbox width

    public function test_resize_landscape_source_square_target_constrains_height(): void
    {
        // source 800x600 (ratio 1.33), target 400x400 (ratio 1.0)
        // target ratio < source ratio → width = 400, height = 400 / 1.33 = 300
        $sizer = new FitSize(400, 400);
        $result = $sizer->resize(new Size(800, 600));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->width);
        $this->assertEquals((int) round(400 / (800 / 600)), $result->height);
    }

    public function test_resize_landscape_source_portrait_target_constrains_width(): void
    {
        // source 1200x600 (ratio 2.0), target 200x400 (ratio 0.5)
        // target ratio < source ratio → width = 200, height = 200 / 2.0 = 100
        $sizer = new FitSize(200, 400);
        $result = $sizer->resize(new Size(1200, 600));
        $this->assertNotNull($result);
        $this->assertEquals(200, $result->width);
        $this->assertEquals(100, $result->height);
    }

    // --- resize() --- portrait source, landscape/square target
    // target ratio > source ratio → constrain height, underflow width

    public function test_resize_portrait_source_square_target_constrains_height(): void
    {
        // source 600x800 (ratio 0.75), target 400x400 (ratio 1.0)
        // target ratio > source ratio → height = 400, width = 400 * 0.75 = 300
        $sizer = new FitSize(400, 400);
        $result = $sizer->resize(new Size(600, 800));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->height);
        $this->assertEquals((int) round(400 * (600 / 800)), $result->width);
    }

    public function test_resize_portrait_source_landscape_target_constrains_height(): void
    {
        // source 400x800 (ratio 0.5), target 600x200 (ratio 3.0)
        // target ratio > source ratio → height = 200, width = 200 * 0.5 = 100
        $sizer = new FitSize(600, 200);
        $result = $sizer->resize(new Size(400, 800));
        $this->assertNotNull($result);
        $this->assertEquals(200, $result->height);
        $this->assertEquals(100, $result->width);
    }

    // --- resize() --- square source

    public function test_resize_square_source_landscape_target_constrains_height(): void
    {
        // source 400x400 (ratio 1.0), target 800x400 (ratio 2.0)
        // target ratio > source ratio → height = 400, width = 400 * 1.0 = 400
        $sizer = new FitSize(800, 400);
        $result = $sizer->resize(new Size(400, 400));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->height);
        $this->assertEquals(400, $result->width);
    }

    public function test_resize_square_source_portrait_target_constrains_width(): void
    {
        // source 400x400 (ratio 1.0), target 400x800 (ratio 0.5)
        // target ratio < source ratio → width = 400, height = 400 / 1.0 = 400
        $sizer = new FitSize(400, 800);
        $result = $sizer->resize(new Size(400, 400));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->width);
        $this->assertEquals(400, $result->height);
    }

    // --- resize() --- matching ratios

    public function test_resize_matching_ratio_produces_exact_target(): void
    {
        // source 800x600, target 400x300 — same ratio
        $sizer = new FitSize(400, 300);
        $result = $sizer->resize(new Size(800, 600));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->width);
        $this->assertEquals(300, $result->height);
    }

    // --- resize() --- upscaling

    public function test_resize_upscales_small_source(): void
    {
        $sizer = new FitSize(800, 600);
        $result = $sizer->resize(new Size(50, 50));
        $this->assertNotNull($result);
        $this->assertGreaterThan(50, $result->width);
        $this->assertGreaterThan(50, $result->height);
    }

    public function test_resize_upscale_fits_within_target_bounds(): void
    {
        $sizer = new FitSize(800, 600);
        $result = $sizer->resize(new Size(50, 50));
        $this->assertNotNull($result);
        $this->assertLessThanOrEqual(800, $result->width);
        $this->assertLessThanOrEqual(600, $result->height);
    }

    // --- critical invariant: resize output must never exceed target bounds ---

    public function test_resize_output_never_exceeds_target_bounds(): void
    {
        $sizer = new FitSize(400, 300);
        foreach ([
            new Size(800, 600),
            new Size(600, 800),
            new Size(100, 100),
            new Size(1000, 200),
            new Size(200, 1000),
            new Size(400, 300),
            new Size(401, 301),
        ] as $source) {
            $result = $sizer->resize($source);
            $this->assertNotNull($result);
            $this->assertLessThanOrEqual(
                400,
                $result->width,
                "Resize width should not exceed target for source {$source->width}x{$source->height}",
            );
            $this->assertLessThanOrEqual(
                300,
                $result->height,
                "Resize height should not exceed target for source {$source->width}x{$source->height}",
            );
        }
    }

    // --- critical invariant: aspect ratio is preserved ---

    public function test_resize_preserves_aspect_ratio(): void
    {
        $sizer = new FitSize(400, 300);
        foreach ([
            new Size(800, 600),
            new Size(600, 800),
            new Size(100, 75),
            new Size(1000, 200),
        ] as $source) {
            $result = $sizer->resize($source);
            $this->assertNotNull($result);
            $sourceRatio = $source->width / $source->height;
            $resultRatio = $result->width / $result->height;
            $this->assertEqualsWithDelta(
                $sourceRatio,
                $resultRatio,
                0.01,
                "Aspect ratio should be preserved for source {$source->width}x{$source->height}",
            );
        }
    }

    // --- finalSize() never exceeds target ---

    public function test_final_size_never_exceeds_target(): void
    {
        $sizer = new FitSize(400, 300);
        foreach ([
            new Size(800, 600),
            new Size(600, 800),
            new Size(400, 300),
            new Size(100, 100),
        ] as $source) {
            $result = $sizer->finalSize($source);
            $this->assertLessThanOrEqual(400, $result->width);
            $this->assertLessThanOrEqual(300, $result->height);
        }
    }

}
