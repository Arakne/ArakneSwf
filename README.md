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
    
    // When you work with large SWF files, you can reach the memory limit of PHP.
    // In this case, you can call `releaseIfOutOfMemory()` during the extraction process to free memory.
    // It can take as parameter the maximum memory usage in bytes, or leave it empty to use 75% of the memory limit.
    // Note: release memory have a performance cost, so use it only when you really need it, or with high limit.
    $extractor->releaseIfOutOfMemory();
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

> [!NOTE]
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
$parser = Swf::fromString(file_get_contents('my_anim.swf'));

$header = $parser->header;

foreach ($parser->tags as $pos) {
    $tag = $parser->parse($pos);
}
```

### Security & error handling

#### Checking file validity

If you want to open untrusted SWF files, you should always check if the file is valid before processing it.
You can do this by calling the `valid()` method on the `SwfFile` instance.
This method takes the maximum expected size of the uncompressed SWF file as an optional parameter, and will return `false` 
if the file has invalid header, or if the length of the file is greater than the expected size.

```php
use Arakne\Swf\SwfFile;

$file = new SwfFile('my_anim.swf');

if (!$file->valid(1_000_000)) { // 1 MB is the maximum expected size
    echo 'Invalid SWF file';
    exit(1);
}
```

> [!NOTE]
> The `valid()` method does not check the content of the SWF file, it only
> checks the header and the length of the file.
> The content of the SWF file is checked during the parsing process, so it's validity is guaranteed 
> by strict error handling.

#### Parsing

The library provides a way to fine tune the error handling, which let you choose between strict error handling 
which will detect any invalid data, but may reject valid files, or a more permissive error handling which will try
to recover from errors, but may produce unexpected results and security issues.

To configure the error handling, simply pass desired error flags to the second parameter of the `SwfFile` constructor.
Error flags are defined in the [`Arakne\Swf\Error\Errors`](./src/Error/Errors.php) class, and you can combine them using bitwise OR operator (`|`).

If an error occurs, and it's not ignored, the library will throw an exception of type `Arakne\Swf\Error\SwfExceptionInterface`.

```php
use Arakne\Swf\SwfFile;
use Arakne\Swf\Error\Errors;

// All errors will be ignored, and the library will try to recover from errors.
$failSafe = new SwfFile('my_anim.swf', errors: Errors::NONE);

// Strict error handling, which will throw an exception on any error.
$strict = new SwfFile('my_anim.swf', errors: Errors::ALL);

// Fine tune the error handling to ignore some errors
$strict = new SwfFile('my_anim.swf', errors: Errors::ALL & ~Errors::INVALID_TAG & ~Errors::UNPROCESSABLE_DATA & ~Errors::EXTRA_DATA);
```

Here the description and recommandations for each error flag:

| Error flag                   | Description                                                                                                                                                                    | Recommandation                                                                                                                                          |
|------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------|
| `Errors::NONE`               | All errors will be silently ignored, and fallback values will be used.                                                                                                         | Use only if you are in sandboxed environment, may produce unexpected results and security issues like DOS.                                              |
| `Errors::ALL`                | All errors will be thrown as exceptions. This is the strictest error handling.                                                                                                 | Useful if you do not trust the origin of SWF files. Don't forget to catch exception when processing the file.                                           |
| `Errors::OUT_OF_BOUNDS`      | If disabled, ignore when a tag is truncated, and the parser try to read beyond the end of the tag or SWF file. The parser will return null value instead.                      | It's really advised to not disable this error, as it may lead to unexpected results and DOS.                                                            |
| `Errors::INVALID_DATA`       | If disabled, ignore when the parser detects invalid data in the SWF file, like invalid ids, or incoherent data. The parser will keep the value and continue the parsing.       | Disable this flag only if you want to recover corrupted or truncated SWF files. It's not recommended to disable this flag when parsing untrusted files. |
| `Errors::EXTRA_DATA`         | Detects when a tag contains extra data after the end of the tag. If disabled, the parser will ignore the extra data and continue parsing.                                      | This flag can be safely disabled, as it cannot lead to unexpected results.                                                                              |
| `Errors::UNKOWN_TAG`         | Raise an error when the parser encounters an unknown tag. If disabled, `UnknownTag` will be returned.                                                                          | Disable this flag if you want to parse raw tags and keep the data. Do not lead to unexpected results nor security issues.                               |
| `Errors::INVALID_TAG`        | If disabled, ignore tags that raise an error when parsing. If enabled, parsing errors will be rethrown.                                                                        | Disable this flag is the safest way to provide a fail-safe parsing, but do not allow parsing highly corrupted or truncated SWF files.                   |
| `Errors::CIRCULAR_REFERENCE` | If disabled, circular references on display list will be ignored, and replaced by an empty display list.                                                                       | It's not recommended to disable this flag, as it will always result to invalid display. Enable only if your goal is to get a very lenient renderer.     |
| `Errors::UNPROCESSABLE_DATA` | If disabled, ignore when the parser encounters data that cannot be processed, like invalid character reference or placement tag. The parser will skip the instruction instead. | This flag can be safely disabled, invalid data will simply ignored or replaced by empty elements.                                                       |

Common flags to use are:
- `Errors::NONE` if you want to parse highly corrupted files and extract as much data as possible. Only use this in a sandboxed environment, and always check the output.
- `Errors::ALL` most strict parser, and so the safest one. Use it if you do not trust the origin of SWF files like on an API endpoint.
- `Errors::OUT_OF_BOUNDS | Errors::INVALID_DATA | Errors::UNKOWN_TAG | Errors::CIRCULAR_REFERENCE` for lenient and safe parsing, which will skip invalid data, without throwing exceptions.
- `Errors::OUT_OF_BOUNDS` if you want to parse truncated SWF files. Always check the output, as it may lead to unexpected results.

#### Rendering

Rendering to SVG is mostly safe, as SVG is a vector format and does not execute any code.
However, rendering to raster images (PNG, JPEG, GIF, WebP) may lead to security issues, as it uses the 
`Imagick` extension to convert SVG to raster images, which is known to have some security issues.

So, if you want to render untrusted SWF files, it's recommended to only render as SVG, and not raster images.

#### AVM interpreter

It's not recommended to use the AVM interpreter on untrusted SWF files, as it may lead to security issues.
But if you want to use it, you can disable all function and object calls by setting the `allowFunctionCall` to `false` when creating the `Processor` instance.
This will prevent the interpreter from executing any code, and only extract constants variables.

#### Found a security issue?

If you found a security issue, unexpected behavior, infinite loop or any other issue, please report it on the [GitHub issues](https://github.com/Arakne/ArakneSwf/issues).
Even with `Errors::NONE` the parsing should safely complete, but the output may be unexpected.

## License and credit

This library is shared under the [LGPLv3](./COPYING.LESSER) license.

Thanks to
- [Thanos Efraimidis](https://www.4real.gr/) for his work on SWF parsing ([SWF.php](https://www.4real.gr/technical-documents-swf-parser.html)), which inspired this project.
- Jindra Petřík with [FFDec](https://github.com/jindrapetrik/jpexs-decompiler), which really helped to understand the SWF format.
- [Open Flash](https://open-flash.github.io/) for resources and documentation about SWF format.
