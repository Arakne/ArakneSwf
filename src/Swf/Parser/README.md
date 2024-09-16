# SWF Parser library

This part of the library is responsible for parsing SWF files. 
It is a low-level library that reads the SWF file and creates a tree of objects that represent the SWF file structure.

This library is independent of the rest of the library because it's derived from the SWF.php created by [Thanos Efraimidis](https://www.4real.gr).

## Usage

The parsing of the whole SWF file is done by the [`Swf`](./Swf.php) class.
The header is accessible through the `header` property, and the list of tags are accessible through the `tags` property.

> NOTE
> The tags from the `tags` property are not parsed. They only store the type and offsets. To parse the tag,
> you need to call the `Swf::parseTag()` method with the tag as argument.
> The result will be one an instance of one of the classes from `Arakne\Swf\Parser\Tag` namespace.

Usage:
```php
use Arakne\Swf\Parser\Swf;

$swf = new Swf(file_get_contents('path/to/file.swf'));

$swf->header; // SwfHeader object

foreach ($swf->tags as $tag) {
    if (in_array($tag->type, TAG_IDS_TO_PARSE)) {
        $parsed = $swf->parseTag($tag);
        // You can now use the parsed tag
    }
}
```

## Components

- [`Swf`](./Swf.php): The main class, facade for parse and access to the SWF file.
- [`SwfHdr`](./SwfHdr.php): Parse the SWF header.
- [`SwfIO`](./SwfIO.php): Low-level class to read primitive types from the SWF file. The instance is mutable, use with caution.
- [`SwfRec`](./SwfRec.php): Parse SWF structures.
- [`SwfTag`](./SwfTag.php): Parse tags from the SWF file.

Classes under the `Arakne\Swf\Parser\Structure` namespace are used to store the parsed data into immutable objects.

## Licence

This library is released under the GPL v3 licence.

**Copyright:**
- Original author (2012): Thanos Efraimidis [source](https://www.4real.gr/technical-documents-swf-parser.html)
- Modified by (2024): Vincent Quatrevieux
