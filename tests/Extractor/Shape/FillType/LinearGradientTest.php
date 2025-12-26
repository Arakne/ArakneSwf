<?php

namespace Arakne\Tests\Swf\Extractor\Shape\FillType;

use Arakne\Swf\Extractor\Shape\FillType\LinearGradient;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\Structure\Record\GradientRecord;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LinearGradientTest extends TestCase
{
    #[Test]
    public function interpolate()
    {
        $start = new LinearGradient(
            new Matrix(1.0, 0.0, 0.0, 1.0, 0, 0),
            new Gradient(
                Gradient::SPREAD_MODE_PAD,
                Gradient::INTERPOLATION_MODE_LINEAR,
                [
                    new GradientRecord(0, new Color(200, 100, 0, 0)),
                    new GradientRecord(255, new Color(10, 128, 255, 255)),
                ]
            )
        );
        $end = new LinearGradient(
            new Matrix(0.5, 0.0, 0.0, 0.5, 10, 20),
            new Gradient(
                Gradient::SPREAD_MODE_PAD,
                Gradient::INTERPOLATION_MODE_LINEAR,
                [
                    new GradientRecord(0, new Color(0, 50, 200, 255)),
                    new GradientRecord(255, new Color(255, 255, 255, 0)),
                ]
            )
        );

        $this->assertEquals($start, $start->interpolate($end, 0));
        $this->assertEquals($end, $start->interpolate($end, 65535));
        $this->assertEqualsWithDelta(
            new LinearGradient(
                new Matrix(0.75, 0.0, 0.0, 0.75, 5, 10),
                new Gradient(
                    Gradient::SPREAD_MODE_PAD,
                    Gradient::INTERPOLATION_MODE_LINEAR,
                    [
                        new GradientRecord(0, new Color(99, 74, 100, 127)),
                        new GradientRecord(255, new Color(132, 191, 255, 127)),
                    ]
                ),
            ),
            $start->interpolate($end, 32768),
            0.0001
        );
    }
}
