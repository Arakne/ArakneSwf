<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Parser\Structure\Record\Shape\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StyleChangeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineFontTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

class DefineFontTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/swf1/new_theater.swf', 2062);
        $tag = DefineFontTag::read($reader);

        $this->assertSame(6, $tag->fontId);
        $this->assertCount(29, $tag->offsetTable);
        $this->assertContainsOnly('int', $tag->offsetTable);
        $this->assertSame(58, $tag->offsetTable[0]);
        $this->assertSame(60, $tag->offsetTable[1]);
        $this->assertSame(1893, $tag->offsetTable[28]);
        $this->assertCount(29, $tag->glyphShapeData);

        $this->assertEquals([new EndShapeRecord()], $tag->glyphShapeData[0]);
        $this->assertEquals([
            new StyleChangeRecord(
                stateNewStyles: false,
                stateLineStyle: true,
                stateFillStyle0: false,
                stateFillStyle1: true,
                stateMoveTo: true,
                moveDeltaX: 38,
                moveDeltaY: -719,
                fillStyle0: 0,
                fillStyle1: 1,
                lineStyle: 0,
                fillStyles: [],
                lineStyles: [],
            ),
            new StraightEdgeRecord(
                generalLineFlag: false,
                verticalLineFlag: false,
                deltaX: 110,
                deltaY: 0,
            ),
            new StraightEdgeRecord(
                generalLineFlag: false,
                verticalLineFlag: true,
                deltaX: 0,
                deltaY: 281,
            ),
            new StraightEdgeRecord(
                generalLineFlag: false,
                verticalLineFlag: false,
                deltaX: -110,
                deltaY: 0,
            ),
            new StraightEdgeRecord(
                generalLineFlag: false,
                verticalLineFlag: true,
                deltaX: 0,
                deltaY: -281,
            ),
            new EndShapeRecord(),
        ], $tag->glyphShapeData[1]);
    }
}
