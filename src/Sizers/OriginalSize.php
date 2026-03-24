<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image\Sizers;

use Joby\Smol\Image\Size;

/**
 * @codeCoverageIgnore there's nothing to test here
 */
class OriginalSize extends Sizer
{

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
        return null;
    }

}
