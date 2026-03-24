<?php

namespace Joby\Smol\Image\Sizers;

use Joby\Smol\Image\Size;
use PHPUnit\Framework\TestCase;

class HeightSizeTest extends TestCase
{

    // --- crop() ---

    public function test_crop_always_returns_null(): void
    {
        $sizer = new HeightSize(400);
        foreach ([
            new Size(800, 600),
            new Size(100, 100),
            new Size(300, 400),
            new Size(800, 200),
        ] as $source) {
            $this->assertNull($sizer->crop($source));
        }
    }

    // --- resize() ---

    public function test_resize_returns_null_when_height_matches(): void
    {
        $sizer = new HeightSize(200);
        $this->assertNull($sizer->resize(new Size(400, 200)));
    }

    public function test_resize_sets_correct_height(): void
    {
        $sizer = new HeightSize(300);
        $result = $sizer->resize(new Size(800, 600));
        $this->assertNotNull($result);
        $this->assertEquals(300, $result->height);
    }

    public function test_resize_preserves_aspect_ratio_landscape(): void
    {
        // 800x600 scaled to height 300 -> 400x300
        $sizer = new HeightSize(300);
        $result = $sizer->resize(new Size(800, 600));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->width);
        $this->assertEquals(300, $result->height);
    }

    public function test_resize_preserves_aspect_ratio_portrait(): void
    {
        // 400x800 scaled to height 400 -> 200x400
        $sizer = new HeightSize(400);
        $result = $sizer->resize(new Size(400, 800));
        $this->assertNotNull($result);
        $this->assertEquals(200, $result->width);
        $this->assertEquals(400, $result->height);
    }

    public function test_resize_preserves_aspect_ratio_square(): void
    {
        $sizer = new HeightSize(300);
        $result = $sizer->resize(new Size(600, 600));
        $this->assertNotNull($result);
        $this->assertEquals(300, $result->width);
        $this->assertEquals(300, $result->height);
    }

    public function test_resize_upscales(): void
    {
        // 100x100 scaled to height 400 -> 400x400
        $sizer = new HeightSize(400);
        $result = $sizer->resize(new Size(100, 100));
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->width);
        $this->assertEquals(400, $result->height);
    }

    public function test_resize_width_is_at_least_one(): void
    {
        // extremely tall source — width should not round to zero
        $sizer = new HeightSize(1);
        $result = $sizer->resize(new Size(1, 10000));
        $this->assertNotNull($result);
        $this->assertGreaterThanOrEqual(1, $result->width);
    }

    // --- aspect ratio invariant ---

    public function test_resize_aspect_ratio_preserved_for_various_sources(): void
    {
        $sizer = new HeightSize(300);
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
        $sizer = new HeightSize(300);
        $result = $sizer->finalSize(new Size(800, 600));
        $this->assertEquals(400, $result->width);
        $this->assertEquals(300, $result->height);
    }

    public function test_final_size_returns_original_when_height_matches(): void
    {
        $sizer = new HeightSize(200);
        $source = new Size(400, 200);
        $result = $sizer->finalSize($source);
        $this->assertEquals(400, $result->width);
        $this->assertEquals(200, $result->height);
    }

}
