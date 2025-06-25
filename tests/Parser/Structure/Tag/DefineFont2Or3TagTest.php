<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\FontLayout;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Record\Shape\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineFont2Or3Tag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

use function array_all;

class DefineFont2Or3TagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/core/core.swf', 38);
        $tag = DefineFont2Or3Tag::read($reader, 3);

        $this->assertSame(3, $tag->version);
        $this->assertFalse($tag->fontFlagsShiftJIS);
        $this->assertFalse($tag->fontFlagsSmallText);
        $this->assertFalse($tag->fontFlagsANSI);
        $this->assertTrue($tag->fontFlagsWideCodes);
        $this->assertTrue($tag->fontFlagsItalic);
        $this->assertTrue($tag->fontFlagsBold);
        $this->assertSame(1, $tag->languageCode);
        $this->assertSame('Verdana', $tag->fontName);
        $this->assertSame(1110, $tag->numGlyphs);
        $this->assertCount(1110, $tag->offsetTable);
        $this->assertSame(4444, $tag->offsetTable[0]);
        $this->assertSame(96915, $tag->offsetTable[1109]);
        $this->assertCount(1110, $tag->glyphShapeTable);
        $this->assertEquals([new EndShapeRecord()], $tag->glyphShapeTable[0]);
        $this->assertCount(1110, $tag->codeTable);
        $this->assertContainsOnly('int', $tag->codeTable);
        $this->assertTrue(array_all($tag->codeTable, fn (int $code) => $code >= 0 && $code <= 2**16-1));
        $this->assertNotNull($tag->layout);
        $this->assertSame(20600, $tag->layout->ascent);
        $this->assertSame(4300, $tag->layout->descent);
        $this->assertCount(1110, $tag->layout->advanceTable);
        $this->assertContainsOnly('int', $tag->layout->advanceTable);
        $this->assertTrue(array_all($tag->layout->advanceTable, fn (int $code) => $code >= -2**15 && $code <= 2**15-1));
        $this->assertCount(1110, $tag->layout->boundsTable);
        $this->assertContainsOnlyInstancesOf(Rectangle::class, $tag->layout->boundsTable);
        $this->assertSame([], $tag->layout->kerningTable);
    }
}
