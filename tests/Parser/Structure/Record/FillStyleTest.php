<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\FillStyle;
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\Structure\Record\GradientRecord;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FillStyleTest extends TestCase
{
    #[Test]
    public function readSolid()
    {
        $reader = new SwfReader("\x00\x10\x20\x30");
        $this->assertEquals(new FillStyle(type: FillStyle::SOLID, color: new Color(16, 32, 48)), FillStyle::read($reader, 1));

        $reader = new SwfReader("\x00\x10\x20\x30\x40");
        $this->assertEquals(new FillStyle(type: FillStyle::SOLID, color: new Color(16, 32, 48, 64)), FillStyle::read($reader, 3));
    }

    #[Test]
    public function readLinearGradient()
    {
        // 10 - linear gradient
        // 00 - empty matrix
        // 93 - spread mode: 2, interpolation mode: 1, 3 records
        // 00000000 - first record: ratio 0, color black
        // 80FF0000 - second record: ratio 128, color red
        // FF00FF00 - third record: ratio 255, color green
        $reader = new SwfReader("\x10\x00\x93\x00\x00\x00\x00\x80\xFF\x00\x00\xFF\x00\xFF\x00");
        $style = FillStyle::read($reader, 1);

        $this->assertSame(FillStyle::LINEAR_GRADIENT, $style->type);
        $this->assertNull($style->color);
        $this->assertNull($style->bitmapId);
        $this->assertNull($style->bitmapMatrix);
        $this->assertNull($style->focalGradient);
        $this->assertEquals(new Gradient(
            spreadMode: Gradient::SPREAD_MODE_REPEAT,
            interpolationMode: Gradient::INTERPOLATION_MODE_LINEAR,
            records: [
                new GradientRecord(0, new Color(0, 0, 0)),
                new GradientRecord(128, new Color(255, 0, 0)),
                new GradientRecord(255, new Color(0, 255, 0)),
            ]
        ), $style->gradient);

        $reader = new SwfReader("\x10\x00\x93\x00\x00\x00\x00\x42\x80\xFF\x00\x00\xFF\xFF\x00\xFF\x00\xAB");
        $style = FillStyle::read($reader, 3);

        $this->assertSame(FillStyle::LINEAR_GRADIENT, $style->type);
        $this->assertNull($style->color);
        $this->assertNull($style->bitmapId);
        $this->assertNull($style->bitmapMatrix);
        $this->assertNull($style->focalGradient);
        $this->assertEquals(new Gradient(
            spreadMode: Gradient::SPREAD_MODE_REPEAT,
            interpolationMode: Gradient::INTERPOLATION_MODE_LINEAR,
            records: [
                new GradientRecord(0, new Color(0, 0, 0, 66)),
                new GradientRecord(128, new Color(255, 0, 0, 255)),
                new GradientRecord(255, new Color(0, 255, 0, 171)),
            ]
        ), $style->gradient);
    }

    #[Test]
    public function readRadialGradient()
    {
        // 12 - linear gradient
        // 00 - empty matrix
        // 93 - spread mode: 2, interpolation mode: 1, 3 records
        // 00000000 - first record: ratio 0, color black
        // 80FF0000 - second record: ratio 128, color red
        // FF00FF00 - third record: ratio 255, color green
        $reader = new SwfReader("\x12\x00\x93\x00\x00\x00\x00\x80\xFF\x00\x00\xFF\x00\xFF\x00");
        $style = FillStyle::read($reader, 1);

        $this->assertSame(FillStyle::RADIAL_GRADIENT, $style->type);
        $this->assertNull($style->color);
        $this->assertNull($style->bitmapId);
        $this->assertNull($style->bitmapMatrix);
        $this->assertNull($style->focalGradient);
        $this->assertEquals(new Gradient(
            spreadMode: Gradient::SPREAD_MODE_REPEAT,
            interpolationMode: Gradient::INTERPOLATION_MODE_LINEAR,
            records: [
                new GradientRecord(0, new Color(0, 0, 0)),
                new GradientRecord(128, new Color(255, 0, 0)),
                new GradientRecord(255, new Color(0, 255, 0)),
            ]
        ), $style->gradient);

        $reader = new SwfReader("\x12\x00\x93\x00\x00\x00\x00\x42\x80\xFF\x00\x00\xFF\xFF\x00\xFF\x00\xAB");
        $style = FillStyle::read($reader, 3);

        $this->assertSame(FillStyle::RADIAL_GRADIENT, $style->type);
        $this->assertNull($style->color);
        $this->assertNull($style->bitmapId);
        $this->assertNull($style->bitmapMatrix);
        $this->assertNull($style->focalGradient);
        $this->assertEquals(new Gradient(
            spreadMode: Gradient::SPREAD_MODE_REPEAT,
            interpolationMode: Gradient::INTERPOLATION_MODE_LINEAR,
            records: [
                new GradientRecord(0, new Color(0, 0, 0, 66)),
                new GradientRecord(128, new Color(255, 0, 0, 255)),
                new GradientRecord(255, new Color(0, 255, 0, 171)),
            ]
        ), $style->gradient);
    }

    #[Test]
    public function readFocalGradient()
    {
        // 13 - focal gradient
        // 00 - empty matrix
        // 93 - spread mode: 2, interpolation mode: 1, 3 records
        // 0000000042 - first record: ratio 0, color black 66 alpha
        // 80FF0000FF - second record: ratio 128, color red 255 alpha
        // FF00FF00AB - third record: ratio 255, color green 171 alpha
        // 8007 - focal point 7.5
        $reader = new SwfReader("\x13\x00\x93\x00\x00\x00\x00\x42\x80\xFF\x00\x00\xFF\xFF\x00\xFF\x00\xAB\x80\x07");
        $style = FillStyle::read($reader, 3);

        $this->assertSame(FillStyle::FOCAL_GRADIENT, $style->type);
        $this->assertNull($style->color);
        $this->assertNull($style->bitmapId);
        $this->assertNull($style->bitmapMatrix);
        $this->assertNull($style->gradient);
        $this->assertEquals(new Gradient(
            spreadMode: Gradient::SPREAD_MODE_REPEAT,
            interpolationMode: Gradient::INTERPOLATION_MODE_LINEAR,
            records: [
                new GradientRecord(0, new Color(0, 0, 0, 66)),
                new GradientRecord(128, new Color(255, 0, 0, 255)),
                new GradientRecord(255, new Color(0, 255, 0, 171)),
            ],
            focalPoint: 7.5
        ), $style->focalGradient);
    }

    #[Test]
    public function readBitmap()
    {
        $this->assertEquals(new FillStyle(type: FillStyle::REPEATING_BITMAP, bitmapId: 5, bitmapMatrix: new Matrix()), FillStyle::read(new SwfReader("\x40\x05\x00\x00"), 1));
        $this->assertEquals(new FillStyle(type: FillStyle::CLIPPED_BITMAP, bitmapId: 5, bitmapMatrix: new Matrix()), FillStyle::read(new SwfReader("\x41\x05\x00\x00"), 1));
        $this->assertEquals(new FillStyle(type: FillStyle::NON_SMOOTHED_REPEATING_BITMAP, bitmapId: 5, bitmapMatrix: new Matrix()), FillStyle::read(new SwfReader("\x42\x05\x00\x00"), 1));
        $this->assertEquals(new FillStyle(type: FillStyle::NON_SMOOTHED_CLIPPED_BITMAP, bitmapId: 5, bitmapMatrix: new Matrix()), FillStyle::read(new SwfReader("\x43\x05\x00\x00"), 1));
    }

    #[Test]
    public function readUnsupportedFullStyle()
    {
        $this->expectExceptionMessage('Unsupported FillStyle type 1');
        FillStyle::read(new SwfReader("\x01"), 1);
    }
}
