<?php

/**
 * smolImage
 * https://github.com/joby-lol/smol-image
 * (c) 2026 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Image;

interface DriverInterface
{

    public function save(Image $image, string $output_path): void;

    public function string(Image $image): string;

}
