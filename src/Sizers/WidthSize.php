<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image\Sizers;

use Joby\Smol\Image\Size;

class WidthSize extends Sizer
{

    /**
     * @param int<1,max> $width
     */
    public function __construct(
        protected readonly int $width,
    ) {}

    /**
     * @inheritDoc
     */
    public function crop(Size $original): Size|null
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function resize(Size $original): Size|null
    {
        if ($this->width != $original->width)
            return new Size(
                $this->width,
                max(
                    (int) round($original->height * ($this->width / $original->width)),
                    1,
                ),
            );
        else
            return null;
    }

}
