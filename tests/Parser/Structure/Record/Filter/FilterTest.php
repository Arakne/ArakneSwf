<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record\Filter;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Filter\BevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ConvolutionFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\DropShadowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\Filter;
use Arakne\Swf\Parser\Structure\Record\Filter\GlowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientBevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientGlowFilter;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class FilterTest extends ParserTestCase
{
    #[Test]
    public function readDropShadow()
    {
        $reader = new SwfReader("\x01\x00\x10\x20\x30\x40\x00\x80\x02\x00\x00\x40\x10\x00\x00\x00\xD0\x00\x00\x00\x02\x00\x80\x00\x43");
        $filters = Filter::readCollection($reader);
        $this->assertCount(1, $filters);

        $this->assertEquals(new DropShadowFilter(
            dropShadowColor: new Color(16, 32, 48, 64),
            blurX: 2.5,
            blurY: 16.25,
            angle: 208.0,
            distance: 2.0,
            strength: 0.5,
            innerShadow: false,
            knockout: true,
            compositeSource: false,
            passes: 3,
        ), $filters[0]);
    }

    #[Test]
    public function readBlur()
    {
        $reader = new SwfReader("\x01\x01\x00\x80\x04\x00\x00\x00\x05\x00\xA0");
        $filters = Filter::readCollection($reader);
        $this->assertCount(1, $filters);

        $this->assertEquals(new BlurFilter(
            blurX: 4.5,
            blurY: 5.0,
            passes: 20,
        ), $filters[0]);
    }

    #[Test]
    public function readGlow()
    {
        $reader = new SwfReader("\x01\x02\x10\x20\x30\x40\x00\x80\x02\x00\x00\x40\x10\x00\x80\x00\x43");
        $filters = Filter::readCollection($reader);
        $this->assertCount(1, $filters);

        $this->assertEquals(new GlowFilter(
            glowColor: new Color(16, 32, 48, 64),
            blurX: 2.5,
            blurY: 16.25,
            strength: 0.5,
            innerGlow: false,
            knockout: true,
            compositeSource: false,
            passes: 3,
        ), $filters[0]);
    }

    #[Test]
    public function readBevel()
    {
        $reader = $this->createReader(__DIR__.'/../../../../Extractor/Fixtures/core/core.swf', 530012);

        $filters = Filter::readCollection($reader);
        $this->assertCount(1, $filters);
        $this->assertEquals(new BevelFilter(
            highlightColor: new Color(255, 255, 255, 255),
            shadowColor: new Color(0, 0, 0, 255),
            blurX: 5.0,
            blurY: 5.0,
            angle: 0.7853851318359375,
            distance: 1.0,
            strength: 1.0,
            innerShadow: true,
            knockout: false,
            compositeSource: true,
            onTop: false,
            passes: 1,
        ), $filters[0]);
    }

    #[Test]
    public function readGradientGlow()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/graphics.swf', 107225);

        $filters = Filter::readCollection($reader);

        $this->assertCount(1, $filters);
        $this->assertEquals(new GradientGlowFilter(
            numColors: 4,
            gradientColors: [
                new Color(255, 0, 0, 0),
                new Color(0, 0, 255, 118),
                new Color(255, 255, 0, 187),
                new Color(255, 0, 255, 255),
            ],
            gradientRatio: [0, 64, 191, 255],
            blurX: 5.0,
            blurY: 5.0,
            angle: 0.7853851318359375,
            distance: 5.0,
            strength: 1.0,
            innerShadow: false,
            knockout: false,
            compositeSource: true,
            onTop: false,
            passes: 1,
        ), $filters[0]);
    }

    #[Test]
    public function readConvolution()
    {
        $reader = new SwfReader("\x01\x05\x03\x04\x00\x00\x20\x40\x00\x00\x40\x40\xcd\xcc\x8c\x3f\x33\x33\x13\x40\x00\x00\x60\x40\x66\x66\x96\x40\xcd\xcc\xbc\x40\x33\x33\xc3\x40\x66\x66\xe6\x40\x66\x66\x06\x41\x9a\x99\x19\x41\xcd\xcc\x2c\x41\x00\x00\x30\x41\x33\x33\x43\x41\x0c\x22\x38\x4e\x01");
        $filters = Filter::readCollection($reader);

        $expected = new ConvolutionFilter(
            matrixX: 3,
            matrixY: 4,
            divisor: 2.5,
            bias: 3.0,
            matrix: [
                1.1, 2.3, 3.5,
                4.7, 5.9, 6.1,
                7.2, 8.4, 9.6,
                10.8, 11.0, 12.2,
            ],
            defaultColor: new Color(12, 34, 56, 78),
            clamp: false,
            preserveAlpha: true,
        );

        $this->assertEqualsWithDelta([$expected], $filters, 0.00001);
    }

    #[Test]
    public function readColorMatrix()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/graphics.swf', 107124);

        $filters = Filter::readCollection($reader);

        $this->assertCount(1, $filters);
        $this->assertEqualsWithDelta(new ColorMatrixFilter(
            matrix: [
                -1.3619517,
                -0.9858965,
                4.0578485,
                0.0,
                6.2150044,
                0.8791733,
                1.871353,
                -1.0405262,
                0.0,
                6.215005,
                -3.036176,
                5.034479,
                -0.2883033,
                0.0,
                6.2150035,
                0.0,
                0.0,
                0.0,
                1.0,
                0.0,
            ]
        ), $filters[0], 0.00001);
    }

    #[Test]
    public function readGradientBevel()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/graphics.swf', 106553);

        $filters = Filter::readCollection($reader);

        $this->assertCount(1, $filters);
        $this->assertEquals(new GradientBevelFilter(
            numColors: 5,
            gradientColors: [
                new Color(255, 0, 0, 255),
                new Color(0, 255, 255, 140),
                new Color(0, 0, 255, 0),
                new Color(255, 255, 0, 78),
                new Color(255, 0, 255, 255),
            ],
            gradientRatio: [0, 64, 128, 191, 255],
            blurX: 5.0,
            blurY: 5.0,
            angle: 0.7853851318359375,
            distance: 5.0,
            strength: 1.0,
            innerShadow: true,
            knockout: false,
            compositeSource: true,
            onTop: false,
            passes: 1,
        ), $filters[0]);
    }

    #[Test]
    public function readCollection()
    {
        $reader = new SwfReader(
            "\x03" .
            "\x00\x10\x20\x30\x40\x00\x80\x02\x00\x00\x40\x10\x00\x00\x00\xD0\x00\x00\x00\x02\x00\x80\x00\x43" .
            "\x01\x00\x80\x04\x00\x00\x00\x05\x00\xA0" .
            "\x02\x10\x20\x30\x40\x00\x80\x02\x00\x00\x40\x10\x00\x80\x00\x43"
        );

        $filters = Filter::readCollection($reader);

        $this->assertCount(3, $filters);
        $this->assertInstanceOf(DropShadowFilter::class, $filters[0]);
        $this->assertInstanceOf(BlurFilter::class, $filters[1]);
        $this->assertInstanceOf(GlowFilter::class, $filters[2]);
    }
}
