<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineTextTag;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

use function var_dump;

class DefineTextTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../Fixtures/Examples1.swf', 134);
        $tag = DefineTextTag::read($reader, 1);

        $this->assertSame(2, $tag->characterId);
        $this->assertEquals(new Rectangle(
            xmin: 0,
            xmax: 808,
            ymin: 74,
            ymax: 563
        ), $tag->textBounds);
        $this->assertEquals(new Matrix(), $tag->textMatrix);
        $this->assertSame(0, $tag->glyphBits);
        $this->assertSame(10, $tag->advanceBits);
        $this->assertCount(1, $tag->textRecords);
        $this->assertSame(1, $tag->textRecords[0]->fontId);
        $this->assertEquals(new Color(0, 0, 0), $tag->textRecords[0]->color);
        $this->assertSame(540, $tag->textRecords[0]->yOffset);
        $this->assertNull($tag->textRecords[0]->xOffset);
        $this->assertSame(600, $tag->textRecords[0]->height);
        $this->assertCount(1, $tag->textRecords[0]->glyphs);
        $this->assertSame(0, $tag->textRecords[0]->glyphs[0]->glyphIndex);
        $this->assertSame(433, $tag->textRecords[0]->glyphs[0]->advance);
    }

    #[Test]
    public function readInvalidGlyphBits()
    {
        $this->expectExceptionMessage('Glyph bits (128) or advance bits (1) are out of bounds (0-32)');
        $this->expectException(ParserInvalidDataException::class);

        $reader = new SwfReader("\x01\x00\x00\x00\x80\x01\x00");
        DefineTextTag::read($reader, 1);
    }

    #[Test]
    public function readInvalidAdvanceBits()
    {
        $this->expectExceptionMessage('Glyph bits (1) or advance bits (128) are out of bounds (0-32)');
        $this->expectException(ParserInvalidDataException::class);

        $reader = new SwfReader("\x01\x00\x00\x00\x01\x80\x00");
        DefineTextTag::read($reader, 1);
    }

    #[Test]
    public function readInvalidGlyphAndAdvanceBitsIgnoreError()
    {
        $reader = new SwfReader("\x01\x00\x00\x00\x80\x80\x00", errors: 0);
        $tag = DefineTextTag::read($reader, 1);

        $this->assertSame(1, $tag->version);
        $this->assertSame(1, $tag->characterId);
        $this->assertSame(128, $tag->glyphBits);
        $this->assertSame(128, $tag->advanceBits);
        $this->assertCount(0, $tag->textRecords);
    }
}
