# Arakne SWF - Parse and extract SWF files in PHP

[![Build](https://github.com/Arakne/ArakneSwf/actions/workflows/build.yml/badge.svg)](https://github.com/Arakne/ArakneSwf/actions/workflows/build.yml)
[![Packagist](https://img.shields.io/packagist/v/arakne/swf)](https://packagist.org/packages/arakne/swf)
[![codecov](https://codecov.io/github/Arakne/ArakneSwf/graph/badge.svg?token=vrelSdfWkp)](https://codecov.io/github/Arakne/ArakneSwf)
[![License](https://img.shields.io/github/license/Arakne/ArakneSwf)](./COPYING.LESSER)

Library to parse SWF tags and extract resources like sprites, images, etc. in pure PHP, without any external dependencies.
Its goal is to simplify processing of multiple SWF files using a script file.

It renders shapes and sprites in SVG format, and can export images in JPEG or PNG format.
It also implements a simple AVM interpreter to extract ActionScript 2 variables.

## Use a CLI application

This project can be used as a simple CLI application, if you simply want to extract resources from a SWF file. 
You can use the `bin/swf-extract` command to do so.

### Installation & show help

PHP 8.4 or higher is required.
Composer is not required, but it's recommended if you want to use PHP scripts.

It may also require some PHP extensions, depending on the features you want to use:
- `gd` for image processing
- `json` to export variables in JSON format
- `xml` for sprite export (performed in SVG format)
- `Imagick` to convert SVG to PNG or JPEG format

> [!NOTE]
> On some systems, the `Imagick` extension does not support well the SVG format, so the conversion may result in a weird image.
> In this case, try to install `rsvg-convert` command (package `librsvg2-bin` on Debian/Ubuntu, `librsvg2-tools` on Fedora) or `inkscape`.
> Inkscape is slower than rsvg, but may produce better results.

```bash
git clone https://github.com/Arakne/ArakneSwf
cd ArakneSwf
bin/swf-extract --help
```

### Usage

Here some examples of how to use the CLI application.
To get the full list of options, run `bin/swf-extract --help`.

```bash
# Extract the root SWF frames. Will create files `export/my_anim/timeline_[frame].svg` for each frame.
bin/swf-extract --timeline my_anim.swf export

# Extract character exported with name "label" on each SWF files
# Will create files `export/[swf_file_basename]/label_[frame].svg` for each frame.
bin/swf-extract -e label sprites/*.swf export

# Extract all exported symbols from each SWF file
bin/swf-extract --all-exported sprites/*.swf export

# Extract all sprites from foo.swf, using a custom filename format
bin/swf-extract --all-sprites foo.swf --output-filename '{name}.{ext}' foo.swf export

# Same as above, but export as PNG with maximum size of 128x128 pixels
bin/swf-extract --all-sprites foo.swf --frame-format png@128 --output-filename '{name}.{ext}' foo.swf export

# Try to resolve variable defined in ActionScript 2 and export them in JSON format
bin/swf-extract --variables swf/*.swf export
```

## Use as a library

To perform more complex operations, you can use the library as a PHP library.

### Installation & basic usage

First you need to install the library using Composer:

```bash
composer require arakne/swf
```

Then you can use the library in your PHP scripts:

```php
<?php
// Include composer autoloader
require_once 'vendor/autoload.php';

use Arakne\Swf\SwfFile;

// Open a SWF file
$file = new SwfFile('my_anim.swf');

// Check if the file is valid
if (!$file->valid()) {
    echo 'Invalid SWF file';
    exit(1);
}

// Now you can use $file to parse the SWF file
```

### Extract resources

You can use this library to render shapes and sprites in SVG format, and to export images in JPEG or PNG format.
To do this, you can use the class [`Arakne\Swf\Extractor\SwfExtractor`](./src/Extractor/SwfExtractor.php).

```php
use Arakne\Swf\SwfFile;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;

$file = new SwfFile('my_anim.swf');

// You can extract some resources directly from the SwfFile instance
// But if you want to extract multiple resources, it's better to use the SwfExtractor class for performance reasons

// Render a sprite exported with name "anim" to SVG.
// Note: the method toSvg() is not available for all character types, so check the type before calling it.
$svg = $file->assetByName('anim')->toSvg();

// Same as above, but using the character ID (doesn't need to be exported)
$svg = $file->assetById(42)->toSvg();

// You can also retrieve all exported assets from the SWF file
foreach ($file->exportedAssets() as $name => $asset) {
    if ($asset instanceof SpriteDefinition) {
        // Render each frame of the sprite as SVG
        for ($f = 0; $f < $asset->framesCount(); $f++) {
            $svg = $asset->toSvg($f);
        }
    }
}

// You can also extract the main animation timeline
$svg = $file->timeline()->toSvg();

// If you want more control over the extraction process, or if you want to extract multiple resources,
// you should use the SwfExtractor class. It improves performance by caching processed sprites and shapes in memory.
$extractor = new SwfExtractor($file);

// Get all shapes present in the SWF file
foreach ($extractor->shapes() as $shape) {
    // Render as SVG string
    $svg = $shape->toSvg();

    // Get the bounding box of the shape for an accurate placement (if needed)
    // Note: bounds are in twips. Divide by 20 to get pixels.
    $bounds = $shape->bounds();
    
    // You can apply a color transform to the shape
    $transformed = $shape->transformColors(new \Arakne\Swf\Parser\Structure\Record\ColorTransform(redMult: 128));
}

// Get all sprites present in the SWF file
foreach ($extractor->sprites() as $sprite) {
    // Render as SVG string
    $svg = $sprite->toSvg();
    
    // You can also render any frame of the sprite
    $framesCount = $sprite->framesCount();
    $otherFrame = $sprite->toSvg(2);

    // Like shapes, sprites have a bounding box
    $bounds = $sprite->bounds();
}

// Get all raster images present in the SWF file
foreach ($extractor->images() as $image) {
    // Render as PNG string
    $png = $image->toPng();
    
    // Render as JPEG string with 70% quality
    $jpeg = $image->toJpeg(70);
}

// Extract the main animation timeline
$anim = $extractor->timeline();
$framesCount = $anim->framesCount();

// Render all frames as SVG
foreach ($anim->toSvgAll() as $frame => $svg) {
    // Process the SVG string
}

// You can also render a single frame
$svg = $anim->toSvg(15);

// Extract a character by its ID
// It can be a shape, sprite or image
$character = $extractor->character(42);

// Create a new renderer engine.
$renderer = new SvgCanvas(new Rectangle(0, 1000, 0, 1000));

// Manually render the character as SVG
$svg = $character->draw($renderer)->render();

// Extract a character by its exported name
// It can be a shape, sprite or image
$character = $extractor->byName('my_sprite');

// You can also extract all exported characters
foreach ($extractor->exported() as $name => $id) {
    $character = $extractor->character($id);
}

// If you want to parse multiple SWF files, it's advised to call `release()` method on the extractor
// when you are done with it. This will free the memory used by the extractor and help the garbage collector.
$extractor->release();
```

If you want a custom rendering format, you can implement [`Arakne\Swf\Extractor\Drawer\DrawerInterface`](./src/Extractor/Drawer/DrawerInterface.php) 
and pass it to the method `draw()` of the character.

### Render as raster image or animated image

You can also render sprites and shapes as raster images, and animations as animated images (GIF, WebP).
The conversion from vector to raster image is done using the `Arakne\Swf\Extractor\Drawer\Converter\Converter` class.

> [!INFO]
> It internally uses `Imagick` to convert SVG to raster images, so you need to have the `Imagick` extension installed.

```php
use Arakne\Swf\SwfFile;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Drawer\Converter\FitSizeResizer;

$file = new SwfFile('my_anim.swf');
$extractor = new SwfExtractor($file);

// Create the converter
$converter = new Converter();

foreach ($extractor->sprites() as $sprite) {
    // Render the sprite to the desired format
    $png = $converter->toPng($sprite);
    $jpeg = $converter->toJpeg($sprite);
    $gif = $converter->toGif($sprite);
    $webp = $converter->toWebp($sprite);

    // You can also specify the frame to render
    $png = $converter->toPng($sprite, 21);
}

// If you want to render as animated image, you can use the `toAnimatedGif()` or `toAnimatedWebp()` methods.
$anim = $converter->toAnimatedWebp($extractor->timeline(), $file->frameRate());

// You can also specify the desired size of the image, and the background color (which is useful for format which don't support transparency)
$converter = new Converter(
    new FitSizeResizer(256, 256), // Resize to fit in a 256x256 box
    '#333', // Background color. Supports hexadecimal format (e.g. '#FF0000' for red), named colors (e.g. 'red'), rgb() format (e.g. 'rgb(255, 0, 0)' for red), or rgba() format (e.g. 'rgba(255, 0, 0, 0.5)' for semi-transparent red)
);

// No more transparency issue: an opaque background is used
$img = $converter->toJpeg($extractor->byName('staticR'));
```

### Extract ActionScript 2 variables & AVM interpreter

This library implements a simple AVM interpreter, which can be used to interpret variable declarations in ActionScript 2, 
and extract the variables from the SWF file.

By default, the interpreter will disable all function and object calls to avoid security issues.
But you can enable them by settings some options to the interpreter.

```php
use Arakne\Swf\SwfFile;
use Arakne\Swf\Avm\Processor;
use Arakne\Swf\Avm\State;

$file = new SwfFile('my_anim.swf');

// To only extract variables as PHP array, you can use the method `variables()`
// Function calls and object calls are disabled by default
$vars = $file->variables();

// You can configure your own interpreter, if you want to enable function calls
// Note: the processor is stateless, so you can reuse it for multiple SWF files
$processor = new Processor(allowFunctionCall: true);
$vars = $file->variables($processor);

// If you want to keep the same context on multiple SWF files, you can instantiate a State
// which will be passed to the processor
// This state can also be used to provide custom functions from PHP
$state = new State();

// The function "my_custom_function" is now available in the AVM interpreter
$state->functions['my_custom_function'] = function () {
    return 42;
};

$file->execute($state, $processor);

// Execute another SWF file with the same state
// So the context will be preserved
$otherSwf = new SwfFile('other_anim.swf');
$otherSwf->execute($state, $processor);

// Now you can access the variables from the two SWF files
$vars = $state->variables;
```

### Process SWF tags

You can also perform low level operations by extracting the tags from the SWF file.

```php
use Arakne\Swf\SwfFile;
use Arakne\Swf\Parser\Structure\Tag\DefineTextTag;
use Arakne\Swf\Parser\Swf;

$file = new SwfFile('my_anim.swf');

// Process all tags and iterate over them
foreach ($file->tags() as $pos => $tag) {
    // The key is the tag position in the SWF file
    $characterId = $pos->id;
    $size = $pos->length;

    if ($tag instanceof DefineTextTag) {
        // Process the tag
    }
}

// You can select tag types to process
foreach ($file->tags(11, 33) as $pos => $tag) {
    // Process the tag
}

// If you want even lower API, you can use `Swf` class from `Parser` package.
$parser = new Swf(file_get_contents('my_anim.swf'));

$header = $parser->header;

foreach ($parser->tags as $pos) {
    $tag = $parser->parse($pos);
}
```

## License and credit

This library is shared using two licenses:
- [LGPLv3](./COPYING.LESSER) for the global library
- [GPLv3](./COPYING) for the [parser library](./src/Parser/README.md)

The parser is derived from the work of Thanos Efraimidis on [SWF.php](https://www.4real.gr/technical-documents-swf-parser.html),
which is licensed under the GPLv3 license.
