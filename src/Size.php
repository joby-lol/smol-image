<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image;

class Size
{

    /**
     * Summary of __construct
     * @param int<1,max> $width
     * @param int<1,max> $height
     */
    public function __construct(
        public readonly int $width,
        public readonly int $height,
    ) {}

    public function ratio(): float
    {
        return $this->width / $this->height;
    }

}
