<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image\Sizers;

use Joby\Smol\Image\Size;

class CoverSize extends Sizer
{

    public readonly Size $target;

    /**
     * @param int<1,max> $width
     * @param int<1,max> $height
     */
    public function __construct(
        int $width,
        int $height,
    )
    {
        $this->target = new Size($width, $height);
    }

    /**
     * @inheritDoc
     */
    public function crop(Size $original): Size|null
    {
        return $this->target;
    }

    /**
     * @inheritDoc
     */
    public function resize(Size $original): Size|null
    {
        if ($this->target->ratio() < $original->ratio())
            return new Size(
                max((int) round($this->target->height * $original->ratio()), 1),
                $this->target->height,
            );
        else
            return new Size(
                $this->target->width,
                max((int) round($this->target->width / $original->ratio()), 1),
            );
    }

}
