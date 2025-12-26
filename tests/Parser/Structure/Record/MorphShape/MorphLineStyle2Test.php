<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record\MorphShape;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphFillStyle;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphLineStyle2;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function str_repeat;

class MorphLineStyle2Test extends ParserTestCase
{
    #[Test]
    public function readCollection()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/morphshape.swf', 1439);

        $styles = MorphLineStyle2::readCollection($reader);

        $this->assertCount(1, $styles);

        $this->assertEquals(new MorphLineStyle2(
            startWidth: 20,
            endWidth: 100,
            startCapStyle: MorphLineStyle2::CAP_ROUND,
            joinStyle: MorphLineStyle2::JOIN_ROUND,
            noHScale: false,
            noVScale: false,
            pixelHinting: false,
            noClose: false,
            endCapStyle: MorphLineStyle2::CAP_ROUND,
            miterLimitFactor: null,
            startColor: new Color(0, 0, 0, 255),
            endColor: new Color(0, 0, 255, 255),
            fillStyle: null,
        ), $styles[0]);
    }

    #[Test]
    public function readWithFillStyle()
    {
        $reader = new SwfReader(
            "\x01" . // count 1
            "\x14\x00" . // start width 20
            "\x28\x00" . // end width 40
            "\xA9" . // flags - start cap square, join miter, hasFill true, noHScale false, noVScale false, pixelHinting true
            "\x02" . // flags - noClose false, end cap square
            "\x80\x03" . // Miter limit factor 3.5
            "\x00\x10\x20\x30\x40\x40\x30\x20\x10" // Fill style solid
        );

        $styles = MorphLineStyle2::readCollection($reader);

        $this->assertCount(1, $styles);

        $this->assertEquals(new MorphLineStyle2(
            startWidth: 20,
            endWidth: 40,
            startCapStyle: MorphLineStyle2::CAP_SQUARE,
            joinStyle: MorphLineStyle2::JOIN_MITER,
            noHScale: false,
            noVScale: false,
            pixelHinting: true,
            noClose: false,
            endCapStyle: MorphLineStyle2::CAP_SQUARE,
            miterLimitFactor: 896, // 3.5 in float
            startColor: null,
            endColor: null,
            fillStyle: new MorphFillStyle(
                type: MorphFillStyle::SOLID,
                startColor: new Color(16, 32, 48, 64),
                endColor: new Color(64, 48, 32, 16),
            ),
        ), $styles[0]);
    }

    #[Test]
    public function readCollectionExtended()
    {
        $reader = new SwfReader(
            "\xFF\xF4\x01" .
            str_repeat("\x14\x00\x28\x00\x00\x00\x10\x20\x30\x40\x40\x30\x20\x10", 500)
        );

        $styles = MorphLineStyle2::readCollection($reader);

        $this->assertCount(500, $styles);
        $this->assertContainsOnlyInstancesOf(MorphLineStyle2::class, $styles);
    }
}
