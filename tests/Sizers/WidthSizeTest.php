<?php

namespace Joby\Smol\Image\Sizers;

use Joby\Smol\Image\Size;
use PHPUnit\Framework\TestCase;

class WidthSizeTest extends TestCase
{

    // --- crop() ---

    public function test_crop_always_returns_null(): void
    {
        $sizer = new WidthSize(400);
        foreach ([
            new Size(800, 600),
            new Size(100, 100),
            new Size(400, 300),
            new Size(200, 800),
        ] as $source) {
            $this->assertNull($sizer->crop($source));
        }
    }

    // --- resize() ---

    public function test_resize_returns_null_when_width_matches(): void
    {
        $sizer = new WidthSize(200);
        $this->assertNull($sizer->resize(new Size(200, 400)));
    }

    public function test_resize_sets_correct_width(): void
    {
        $sizer = new WidthSize(400);
        $result = $sizer->resize(new Size(800, 600));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->width);
    }

    public function test_resize_preserves_aspect_ratio_landscape(): void
    {
        // 800x600 scaled to width 400 -> 400x300
        $sizer = new WidthSize(400);
        $result = $sizer->resize(new Size(800, 600));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->width);
        $this->assertEquals(300, $result->height);
    }

    public function test_resize_preserves_aspect_ratio_portrait(): void
    {
        // 400x800 scaled to width 200 -> 200x400
        $sizer = new WidthSize(200);
        $result = $sizer->resize(new Size(400, 800));
        $this->assertNotNull($result);
        $this->assertEquals(200, $result->width);
        $this->assertEquals(400, $result->height);
    }

    public function test_resize_preserves_aspect_ratio_square(): void
    {
        $sizer = new WidthSize(300);
        $result = $sizer->resize(new Size(600, 600));
        $this->assertNotNull($result);
        $this->assertEquals(300, $result->width);
        $this->assertEquals(300, $result->height);
    }

    public function test_resize_upscales(): void
    {
        // 100x100 scaled to width 400 -> 400x400
        $sizer = new WidthSize(400);
        $result = $sizer->resize(new Size(100, 100));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->width);
        $this->assertEquals(400, $result->height);
    }

    public function test_resize_height_is_at_least_one(): void
    {
        // extremely wide source — height should not round to zero
        $sizer = new WidthSize(1);
        $result = $sizer->resize(new Size(10000, 1));
        $this->assertNotNull($result);
        $this->assertGreaterThanOrEqual(1, $result->height);
    }

    // --- aspect ratio invariant ---

    public function test_resize_aspect_ratio_preserved_for_various_sources(): void
    {
        $sizer = new WidthSize(300);
        foreach ([
            new Size(900, 600),
            new Size(600, 900),
            new Size(100, 75),
            new Size(1000, 333),
        ] as $source) {
            $result = $sizer->resize($source);
            $this->assertNotNull($result);
            $this->assertEqualsWithDelta(
                $source->width / $source->height,
                $result->width / $result->height,
                0.01,
                "Aspect ratio not preserved for {$source->width}x{$source->height}",
            );
        }
    }

    // --- finalSize() ---

    public function test_final_size_returns_resized_dimensions(): void
    {
        $sizer = new WidthSize(400);
        $result = $sizer->finalSize(new Size(800, 600));
        $this->assertEquals(400, $result->width);
        $this->assertEquals(300, $result->height);
    }

    public function test_final_size_returns_original_when_width_matches(): void
    {
        $sizer = new WidthSize(200);
        $source = new Size(200, 400);
        $result = $sizer->finalSize($source);
        $this->assertEquals(200, $result->width);
        $this->assertEquals(400, $result->height);
    }

}
