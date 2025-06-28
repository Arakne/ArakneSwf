<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record\MorphShape;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphFillStyle;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphGradient;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphGradientRecord;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function str_repeat;

class MorphFillStyleTest extends ParserTestCase
{
    #[Test]
    public function readCollection()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/morphshape.swf', 1429);

        $styles = MorphFillStyle::readCollection($reader);

        $this->assertCount(1, $styles);

        $this->assertEquals(new MorphFillStyle(
            type: MorphFillStyle::SOLID,
            startColor: new Color(255, 0, 0, 255),
            endColor: new Color(0, 255, 0, 255),
        ), $styles[0]);
    }

    #[Test]
    public function readCollectionExtended()
    {
        $reader = new SwfReader("\xFF\xF4\x01" . str_repeat("\x00\x10\x20\x30\x40\x40\x30\x20\x10", 500));
        $styles = MorphFillStyle::readCollection($reader);

        $this->assertCount(500, $styles);
        $this->assertContainsOnly(MorphFillStyle::class, $styles);
    }

    #[Test]
    public function readSolid()
    {
        $reader = new SwfReader("\x00\x10\x20\x30\x40\x40\x30\x20\x10");
        $style = MorphFillStyle::read($reader);

        $this->assertEquals(new MorphFillStyle(
            type: MorphFillStyle::SOLID,
            startColor: new Color(16, 32, 48, 64),
            endColor: new Color(64, 48, 32, 16),
        ), $style);
    }

    #[Test]
    public function readLinearGradient()
    {
        $reader = new SwfReader(
            "\x10" . // Type
            "\x00" . // Start gradient matrix (empty)
            "\x08\x48" . // End gradient matrix translate (2, 4)
            "\x43" . // Gradients flags : 0b0100_0011 - spread 1, interpolation 0, 3 records
            "\x00\xFF\x00\x00\xFF\x00\xFF\x00\x00\x00" . // Record 1
            "\x80\x00\xFF\x00\xFF\x40\x00\xFF\x00\x00" . // Record 2
            "\xFF\x00\x00\xFF\xFF\x80\x00\x00\xFF\x00" // Record 3
        );
        $style = MorphFillStyle::read($reader);

        $this->assertEquals(new MorphFillStyle(
            type: MorphFillStyle::LINEAR_GRADIENT,
            startGradientMatrix: new Matrix(),
            endGradientMatrix: new Matrix(translateX: 2, translateY: 4),
            gradient: new MorphGradient(
                spreadMode: MorphGradient::INTERPOLATION_MODE_LINEAR,
                interpolationMode: MorphGradient::SPREAD_MODE_PAD,
                records: [
                    new MorphGradientRecord(
                        startRatio: 0,
                        startColor: new Color(255, 0, 0, 255),
                        endRatio: 0,
                        endColor: new Color(255, 0, 0, 0)
                    ),
                    new MorphGradientRecord(
                        startRatio: 128,
                        startColor: new Color(0, 255, 0, 255),
                        endRatio: 64,
                        endColor: new Color(0, 255, 0, 0)
                    ),
                    new MorphGradientRecord(
                        startRatio: 255,
                        startColor: new Color(0, 0, 255, 255),
                        endRatio: 128,
                        endColor: new Color(0, 0, 255, 0)
                    ),
                ]
            )
        ), $style);
    }

    #[Test]
    public function readRadialGradient()
    {
        $reader = new SwfReader(
            "\x12" . // Type
            "\x00" . // Start gradient matrix (empty)
            "\x08\x48" . // End gradient matrix translate (2, 4)
            "\x43" . // Gradients flags : 0b0100_0011 - spread 1, interpolation 0, 3 records
            "\x00\xFF\x00\x00\xFF\x00\xFF\x00\x00\x00" . // Record 1
            "\x80\x00\xFF\x00\xFF\x40\x00\xFF\x00\x00" . // Record 2
            "\xFF\x00\x00\xFF\xFF\x80\x00\x00\xFF\x00" // Record 3
        );
        $style = MorphFillStyle::read($reader);

        $this->assertEquals(new MorphFillStyle(
            type: MorphFillStyle::RADIAL_GRADIENT,
            startGradientMatrix: new Matrix(),
            endGradientMatrix: new Matrix(translateX: 2, translateY: 4),
            gradient: new MorphGradient(
                spreadMode: MorphGradient::INTERPOLATION_MODE_LINEAR,
                interpolationMode: MorphGradient::SPREAD_MODE_PAD,
                records: [
                    new MorphGradientRecord(
                        startRatio: 0,
                        startColor: new Color(255, 0, 0, 255),
                        endRatio: 0,
                        endColor: new Color(255, 0, 0, 0)
                    ),
                    new MorphGradientRecord(
                        startRatio: 128,
                        startColor: new Color(0, 255, 0, 255),
                        endRatio: 64,
                        endColor: new Color(0, 255, 0, 0)
                    ),
                    new MorphGradientRecord(
                        startRatio: 255,
                        startColor: new Color(0, 0, 255, 255),
                        endRatio: 128,
                        endColor: new Color(0, 0, 255, 0)
                    ),
                ]
            )
        ), $style);
    }

    #[Test]
    public function readFocalGradient()
    {
        $reader = new SwfReader(
            "\x13" . // Type
            "\x00" . // Start gradient matrix (empty)
            "\x08\x48" . // End gradient matrix translate (2, 4)
            "\x43" . // Gradients flags : 0b0100_0011 - spread 1, interpolation 0, 3 records
            "\x00\xFF\x00\x00\xFF\x00\xFF\x00\x00\x00" . // Record 1
            "\x80\x00\xFF\x00\xFF\x40\x00\xFF\x00\x00" . // Record 2
            "\xFF\x00\x00\xFF\xFF\x80\x00\x00\xFF\x00" . // Record 3
            "\x40\x03" // Focal point
        );
        $style = MorphFillStyle::read($reader);

        $this->assertEquals(new MorphFillStyle(
            type: MorphFillStyle::FOCAL_RADIAL_GRADIENT,
            startGradientMatrix: new Matrix(),
            endGradientMatrix: new Matrix(translateX: 2, translateY: 4),
            gradient: new MorphGradient(
                spreadMode: MorphGradient::INTERPOLATION_MODE_LINEAR,
                interpolationMode: MorphGradient::SPREAD_MODE_PAD,
                records: [
                    new MorphGradientRecord(
                        startRatio: 0,
                        startColor: new Color(255, 0, 0, 255),
                        endRatio: 0,
                        endColor: new Color(255, 0, 0, 0)
                    ),
                    new MorphGradientRecord(
                        startRatio: 128,
                        startColor: new Color(0, 255, 0, 255),
                        endRatio: 64,
                        endColor: new Color(0, 255, 0, 0)
                    ),
                    new MorphGradientRecord(
                        startRatio: 255,
                        startColor: new Color(0, 0, 255, 255),
                        endRatio: 128,
                        endColor: new Color(0, 0, 255, 0)
                    ),
                ],
                focalPoint: 3.25,
            )
        ), $style);
    }

    #[Test]
    public function readRepeatingBitmap()
    {
        $reader = new SwfReader(
            "\x40" . // Type
            "\x02\x01" . // Bitmap ID (258)
            "\x00" . // Start matrix (empty)
            "\x08\x48" // End matrix translate (2, 4)
        );
        $style = MorphFillStyle::read($reader);

        $this->assertEquals(new MorphFillStyle(
            type: MorphFillStyle::REPEATING_BITMAP,
            bitmapId: 258,
            startBitmapMatrix: new Matrix(),
            endBitmapMatrix: new Matrix(translateX: 2, translateY: 4),
        ), $style);
    }

    #[Test]
    public function readClippedBitmap()
    {
        $reader = new SwfReader(
            "\x41" . // Type
            "\x02\x01" . // Bitmap ID (258)
            "\x00" . // Start matrix (empty)
            "\x08\x48" // End matrix translate (2, 4)
        );
        $style = MorphFillStyle::read($reader);

        $this->assertEquals(new MorphFillStyle(
            type: MorphFillStyle::CLIPPED_BITMAP,
            bitmapId: 258,
            startBitmapMatrix: new Matrix(),
            endBitmapMatrix: new Matrix(translateX: 2, translateY: 4),
        ), $style);
    }

    #[Test]
    public function readNonSmoothedRepeatingBitmap()
    {
        $reader = new SwfReader(
            "\x42" . // Type
            "\x02\x01" . // Bitmap ID (258)
            "\x00" . // Start matrix (empty)
            "\x08\x48" // End matrix translate (2, 4)
        );
        $style = MorphFillStyle::read($reader);

        $this->assertEquals(new MorphFillStyle(
            type: MorphFillStyle::NON_SMOOTHED_REPEATING_BITMAP,
            bitmapId: 258,
            startBitmapMatrix: new Matrix(),
            endBitmapMatrix: new Matrix(translateX: 2, translateY: 4),
        ), $style);
    }

    #[Test]
    public function readNonSmoothedClippedBitmap()
    {
        $reader = new SwfReader(
            "\x43" . // Type
            "\x02\x01" . // Bitmap ID (258)
            "\x00" . // Start matrix (empty)
            "\x08\x48" // End matrix translate (2, 4)
        );
        $style = MorphFillStyle::read($reader);

        $this->assertEquals(new MorphFillStyle(
            type: MorphFillStyle::NON_SMOOTHED_CLIPPED_BITMAP,
            bitmapId: 258,
            startBitmapMatrix: new Matrix(),
            endBitmapMatrix: new Matrix(translateX: 2, translateY: 4),
        ), $style);
    }

    #[Test]
    public function readUnsupportedType()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown MorphFillStyle type: 1');

        $reader = new SwfReader("\x01");
        MorphFillStyle::read($reader);
    }

    #[Test]
    public function readUnsupportedTypeIgnoreError()
    {
        $reader = new SwfReader("\x01", errors: 0);
        $this->assertEquals(new MorphFillStyle(type: 1), MorphFillStyle::read($reader));
    }
}
