<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\DefineFontInfoTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

class DefineFontInfoTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/swf1/new_theater.swf', 3993);
        $tag = DefineFontInfoTag::read($reader, 1, 4039);

        $this->assertSame(1, $tag->version);
        $this->assertSame(6, $tag->fontId);
        $this->assertSame('Zurich Blk BT', $tag->fontName);
        $this->assertFalse($tag->fontFlagsSmallText);
        $this->assertTrue($tag->fontFlagsShiftJIS);
        $this->assertFalse($tag->fontFlagsANSI);
        $this->assertFalse($tag->fontFlagsItalic);
        $this->assertTrue($tag->fontFlagsBold);
        $this->assertFalse($tag->fontFlagsWideCodes);
        $this->assertSame([32, 39, 48, 50, 65, 70, 72, 76, 83, 97, 98, 99, 100, 101, 102, 104, 105, 107, 108, 109, 110, 111, 112, 114, 115, 116, 117, 119, 122], $tag->codeTable);
        $this->assertNull($tag->languageCode);
    }
}
