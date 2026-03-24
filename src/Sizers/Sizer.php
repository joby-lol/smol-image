<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image\Sizers;

use Joby\Smol\Image\Image;
use Joby\Smol\Image\Size;

abstract class Sizer
{

    abstract public function resize(Size $original): Size|null;

    abstract public function crop(Size $original): Size|null;

    public function finalSize(Size $original): Size
    {
        return $this->crop($original)
            ?? $this->resize($original)
            ?? $original;
    }

}
