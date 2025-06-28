<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsLosslessTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function gzcompress;

class DefineBitsLosslessTagTest extends TestCase
{
    #[Test]
    public function readInvalidImageFormat()
    {
        $this->expectException(ParserInvalidDataException::class);
        $this->expectExceptionMessage('Invalid bitmap format 66 for DefineBitsLossless tag (version 1)');

        $reader = new SwfReader("\x01\x00\x42\x01\x01".gzcompress('pixels data'));
        DefineBitsLosslessTag::read($reader, 1, $reader->end);
    }

    #[Test]
    public function readInvalidImageFormatIgnoreError()
    {
        $reader = new SwfReader("\x01\x00\x42\x01\x00\x01\x00".gzcompress('pixels data'), errors: 0);
        $tag = DefineBitsLosslessTag::read($reader, 1, $reader->end);

        $this->assertSame(1, $tag->version);
        $this->assertSame(66, $tag->bitmapFormat);
        $this->assertSame(1, $tag->characterId);
        $this->assertSame(1, $tag->bitmapWidth);
        $this->assertSame(1, $tag->bitmapHeight);
        $this->assertNull($tag->colorTable);
        $this->assertSame('pixels data', $tag->pixelData);
    }
}
