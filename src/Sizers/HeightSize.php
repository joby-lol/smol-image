<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image\Sizers;

use Joby\Smol\Image\Size;

class HeightSize extends Sizer
{

    /**
     * @param int<1,max> $height
     */
    public function __construct(
        protected readonly int $height,
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
        if ($this->height != $original->height)
            return new Size(
                max(
                    (int) round($original->width * ($this->height / $original->height)),
                    1,
                ),
                $this->height,
            );
        else
            return null;
    }

}
