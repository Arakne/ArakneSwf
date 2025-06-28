<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphFillStyle;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphLineStyle;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphLineStyle2;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Record\Shape\ShapeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShape2Tag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

class DefineMorphShape2TagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/core/core.swf', 2215533);
        $tag = DefineMorphShape2Tag::read($reader);

        $this->assertSame(1558, $tag->characterId);
        $this->assertEquals(new Rectangle(
            xmin: -770,
            xmax: 770,
            ymin: -770,
            ymax: 770,
        ), $tag->startBounds);
        $this->assertEquals(new Rectangle(
            xmin: -710,
            xmax: 710,
            ymin: -710,
            ymax: 710,
        ), $tag->endBounds);
        $this->assertEquals(new Rectangle(
            xmin: -720,
            xmax: 720,
            ymin: -720,
            ymax: 720,
        ), $tag->startEdgeBounds);
        $this->assertEquals(new Rectangle(
            xmin: -660,
            xmax: 660,
            ymin: -660,
            ymax: 660,
        ), $tag->endEdgeBounds);

        $this->assertEquals([], $tag->fillStyles);

        $this->assertEquals([
            new MorphLineStyle2(
                startWidth: 100,
                endWidth: 100,
                startCapStyle: 0,
                joinStyle: 0,
                noHScale: false,
                noVScale: false,
                pixelHinting: false,
                noClose: false,
                endCapStyle: 0,
                miterLimitFactor: null,
                startColor: new Color(41, 38, 31, 255),
                endColor: new Color(41, 38, 31, 255),
                fillStyle: null,
            )
        ], $tag->lineStyles);

        $this->assertTrue($tag->usesScalingStrokes);
        $this->assertFalse($tag->usesNonScalingStrokes);

        $this->assertCount(13, $tag->startEdges);
        $this->assertContainsOnlyInstancesOf(ShapeRecord::class, $tag->startEdges);
        $this->assertCount(13, $tag->endEdges);
        $this->assertContainsOnlyInstancesOf(ShapeRecord::class, $tag->endEdges);
    }
}
