<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\FillStyle;
use Arakne\Swf\Parser\Structure\Record\LineStyle;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;

class LineStyleTest extends TestCase
{
    #[Test]
    public function readCollectionWithoutAlpha()
    {
        $reader = new SwfReader("\x03\x01\x00\xFF\xFF\xFF\x10\x00\x20\x30\x40\xAB\x42\x00\x05\xFE");
        $styles = LineStyle::readCollection($reader, 2);

        $this->assertCount(3, $styles);

        $this->assertSame(1, $styles[0]->width);
        $this->assertEquals(new Color(255, 255, 255), $styles[0]->color);

        $this->assertSame(16, $styles[1]->width);
        $this->assertEquals(new Color(32, 48, 64), $styles[1]->color);

        $this->assertSame(17067, $styles[2]->width);
        $this->assertEquals(new Color(0, 5, 254), $styles[2]->color);
    }

    #[Test]
    public function readCollectionWithAlpha()
    {
        $reader = new SwfReader("\x03\x01\x00\xFF\xFF\xFF\xFF\x10\x00\x20\x30\x40\x50\xAB\x42\x00\x05\xFE\xA0");
        $styles = LineStyle::readCollection($reader, 3);

        $this->assertCount(3, $styles);

        $this->assertSame(1, $styles[0]->width);
        $this->assertEquals(new Color(255, 255, 255, 255), $styles[0]->color);

        $this->assertSame(16, $styles[1]->width);
        $this->assertEquals(new Color(32, 48, 64, 80), $styles[1]->color);

        $this->assertSame(17067, $styles[2]->width);
        $this->assertEquals(new Color(0, 5, 254, 160), $styles[2]->color);
    }

    #[Test]
    public function readCollectionExtendedSize()
    {
        $reader = new SwfReader("\xFF\xE8\x03" . str_repeat("\x01\x00\xFF\xFF\xFF", 1000));
        $styles = LineStyle::readCollection($reader, 2);

        $this->assertCount(1000, $styles);

        foreach ($styles as $style) {
            $this->assertSame(1, $style->width);
            $this->assertEquals(new Color(255, 255, 255), $style->color);
        }
    }

    #[Test]
    public function readCollectionV4WithFillStyle()
    {
        // 01 - count: 1
        // 0001 - width: 256
        // 9B - cap: 2, join: 1, hasFill: true, noHScale: false, noVScale: true, pixelHinting: true
        // 05 - no close: true, end cap: 1
        // 400500 - repeating bitmap 5
        // 00 - empty matrix
        $reader = new SwfReader("\x01\x00\x01\x9B\x05\x40\x05\x00\x00");
        $styles = LineStyle::readCollection($reader, 4);

        $this->assertCount(1, $styles);

        $this->assertEquals(new LineStyle(
            width: 256,
            color: null,
            startCapStyle: 2,
            joinStyle: 1,
            hasFillFlag: true,
            noHScaleFlag: false,
            noVScaleFlag: true,
            pixelHintingFlag: true,
            noClose: true,
            endCapStyle: 1,
            fillType: new FillStyle(
                type: FillStyle::REPEATING_BITMAP,
                bitmapId: 5,
                bitmapMatrix: new Matrix(),
            )
        ), $styles[0]);
    }
}
