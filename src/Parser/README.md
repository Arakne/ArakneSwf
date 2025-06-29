# SWF Parser library

This part of the library is responsible for parsing SWF files. 
It is a low-level library that reads the SWF file and creates a tree of objects that represent the SWF file structure.

## Usage

The parsing of the whole SWF file is done by the [`Swf`](./Swf.php) class.
The header is accessible through the `header` property, and the list of tags are accessible through the `tags` property.

> [!NOTE]
> The tags from the `tags` property are not parsed. They only store the type and offsets. To parse the tag,
> you need to call the `Swf::parse()` method with the tag as argument.
> The result will be one an instance of one of the classes from `Arakne\Swf\Parser\Tag` namespace.

Usage:

```php
use Arakne\Swf\Parser\Swf;

$swf = Swf::fromString(file_get_contents('path/to/file.swf'));

$swf->header; // SwfHeader object

foreach ($swf->tags as $tag) {
    if (in_array($tag->type, TAG_IDS_TO_PARSE)) {
        $parsed = $swf->parse($tag);
        // You can now use the parsed tag
    }
}

// Parse a tag by its character id
$character = $swf->parse($swf->dictionary[12]);
```

## Error handling

The library allows to fine tune the error reporting to get a strict or lenient parsing.
When an error occurs, and it's configured to be reported, an exception of type `Arakne\Swf\Parser\Error\ParserExceptionInterface` is thrown.
When an error is disabled, the parser will try to provide a partial or default value for the field.

See [`Errors`](./Error/Errors.php) to get the list of available errors.

```php
use Arakne\Swf\Error\Errors;
use Arakne\Swf\Parser\Error\ParserExceptionInterface;
use Arakne\Swf\Parser\Swf;

// Enable all errors, but accept unknown tags
$swf = Swf::fromString(file_get_contents('path/to/file.swf'), errors: Errors::ALL & ~Errors::UNKNOWN_TAG);

foreach ($swf->tags as $tag) {
    try {
        $parsed = $swf->parse($tag);
        // Use the parsed tag
    } catch (ParserExceptionInterface $e) {
        // Handle the error, e.g. log it
        echo "Error parsing tag {$tag->type}: {$e->getMessage()}\n";
    }
}

// Disable all errors, so the parser will try to always parse all tags
// This mode also allows to parse truncated SWF files
$swf = Swf::fromString(file_get_contents('path/to/file.swf'), errors: Errors::NONE);

foreach ($swf->tags as $tag) {
    // No error will be thrown, but the tag data may be invalid
    $parsed = $swf->parse($tag);
}
```

## Components

- [`Swf`](./Swf.php): The main class, facade for parse and access to the SWF file.
- [`SwfReader`](./SwfReader.php): Low-level class to read primitive types from the SWF file. The instance is mutable, use with caution.
- [`SwfTag`](./Structure/SwfTag.php): Parse tags from the SWF file.

Classes under the `Arakne\Swf\Parser\Structure` namespace are used to store the parsed data into immutable objects.
