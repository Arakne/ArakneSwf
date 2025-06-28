<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphFillStyle;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphLineStyle;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Record\Shape\ShapeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShapeTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefineMorphShapeTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/core/core.swf', 1287509);
        $tag = DefineMorphShapeTag::read($reader);

        $this->assertSame(571, $tag->characterId);
        $this->assertEquals(new Rectangle(
            xmin: -464,
            xmax: 99,
            ymin: -348,
            ymax: 389,
        ), $tag->startBounds);
        $this->assertEquals(new Rectangle(
            xmin: -664,
            xmax: -101,
            ymin: -348,
            ymax: 389,
        ), $tag->endBounds);

        $this->assertEquals([
            new MorphFillStyle(
                type: MorphFillStyle::SOLID,
                startColor: new Color(255, 255, 0, 255),
                endColor: new Color(255, 255, 0, 255),
            )
        ], $tag->fillStyles);

        $this->assertEquals([
            new MorphLineStyle(
                startWidth: 0,
                endWidth: 0,
                startColor: new Color(0, 0, 0, 0),
                endColor: new Color(0, 0, 0, 0),
            )
        ], $tag->lineStyles);

        $this->assertCount(11, $tag->startEdges);
        $this->assertContainsOnlyInstancesOf(ShapeRecord::class, $tag->startEdges);
        $this->assertCount(11, $tag->endEdges);
        $this->assertContainsOnlyInstancesOf(ShapeRecord::class, $tag->endEdges);
    }
}
