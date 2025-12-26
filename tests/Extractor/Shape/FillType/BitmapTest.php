<?php

namespace Arakne\Tests\Swf\Extractor\Shape\FillType;

use Arakne\Swf\Extractor\Image\EmptyImage;
use Arakne\Swf\Extractor\Shape\FillType\Bitmap;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BitmapTest extends TestCase
{
    #[Test]
    public function interpolate()
    {
        $start = new Bitmap(
            new EmptyImage(0),
            new Matrix(
                1.2,
                0.7,
                12.3,
                4.5,
                42,
                -21,
            ),
            true,
            false,
        );
        $end = new Bitmap(
            new EmptyImage(0),
            new Matrix(
                -0.5,
                2.3,
                7.8,
                -1.1,
                -33,
                17,
            ),
            true,
            false,
        );

        $this->assertEquals($start, $start->interpolate($end, 0));
        $this->assertEquals($end, $start->interpolate($end, 65535));
        $this->assertEqualsWithDelta(
            new Bitmap(
                new EmptyImage(0),
                new Matrix(
                    0.35,
                    1.5,
                    10.05,
                    1.7,
                    4,
                    -1,
                ),
                true,
                false,
            ),
            $start->interpolate($end, 32768),
            0.0001
        );
    }
}
