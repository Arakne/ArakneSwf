<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineTextTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

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
}
