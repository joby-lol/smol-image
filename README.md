# smolImage

A lightweight and minimalist no-dependency image transformation library for PHP 8.1+.

## Installation
```bash
composer require joby-lol/smol-image
```

## About

smolImage eschews kitchen-sink image libraries in favor of focusing on sizing and converting. It resizes, fits, and crops images using whatever backend your server provides. It is deliberately as self-contained as possible — no Composer dependencies, just PHP and the image transformation extensions or CLI tools you already have.

## Basic Usage
```php
use Joby\Smol\Image\SmolImage;

// Resize and save
SmolImage::load('/path/to/source.jpg')
    ->cover(800, 600)
    ->save('/path/to/output.webp');

// Or get the result as a string
$data = SmolImage::load('/path/to/source.jpg')
    ->fit(400, 300)
    ->jpeg()
    ->string();
```

## Sizing
```php
// Scale and crop to exactly fill the given dimensions (upscales if necessary)
$resized = $image->cover($width, $height);

// Scale to fit within the given bounding box (upscales if necessary)
$resized = $image->fit($width, $height);

// Scale only on one dimension (upscales if necessary)
$resized = $image->fit($width, null);
$resized = $image->fit(null, $height);

// No transformation — output at original size
$original = $image->original();
```

## Output Format and Quality

Default output format is WebP at quality 85. These can be changed globally or per image:
```php
// Global defaults
SmolImage::setFormat(Format::jpeg);
SmolImage::setQuality(90);

// Per image — returns a new immutable instance
$image->jpeg();
$image->webp();
$image->png();
$image->quality(90);
```

Quality is on a scale of 0–100 and is interpreted appropriately per format. PNG compression is derived from this value automatically.

## Blurring
```php
// Blur by a factor of 80/10
$image->blur();

// Blur by a factor of 50/100
$image->blur(50);

// Restore to zero blurring
$image->blur(0);
```

Blurring is on a somewhat arbitrary scale of 0-100 and is interpreted differently by different drivers.

## Drivers

smolImage defaults to using GD as its backend, because this is available most widely. There are also options to use Imagick or Unix command-line `convert` calls.
```php
use Joby\Smol\Image\Drivers\GdDriver;
use Joby\Smol\Image\Drivers\ImagickDriver;
use Joby\Smol\Image\Drivers\CliConvertDriver;

SmolImage::setDriver(new ImagickDriver());        // requires ext-imagick
SmolImage::setDriver(new GdDriver());             // requires ext-gd (default)
SmolImage::setDriver(new CliConvertDriver());     // requires `convert` CLI
```

`CliConvertDriver` accepts an optional path to the `convert` executable:
```php
SmolImage::setDriver(new CliConvertDriver('/usr/local/bin/convert'));
```

The `Image` object is immutable — all transformation methods return a new instance, making it safe to reuse a base image for multiple outputs:
```php
$source = SmolImage::load('/path/to/photo.jpg')->webp();

$source->cover(1200, 630)->save('/path/to/cover.webp');
$source->cover(400, 400)->save('/path/to/thumb.webp');
$source->fit(1600, 1200)->save('/path/to/full.webp');
```

## Requirements

Fully tested on PHP 8.3+, static analysis for PHP 8.1+. Requires at least one of: `ext-gd`, `ext-imagick`, or ImageMagick's `convert` CLI tool with `exec()` enabled.

## License

MIT License - See [LICENSE](LICENSE) file for details.