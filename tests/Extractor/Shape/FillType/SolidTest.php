<?php

namespace Arakne\Tests\Swf\Extractor\Shape\FillType;

use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Parser\Structure\Record\Color;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SolidTest extends TestCase
{
    #[Test]
    public function interpolate()
    {
        $start = new Solid(new Color(42, 112, 205, 5));
        $end = new Solid(new Color(202, 50, 100));

        $this->assertEquals(new Solid(new Color(42, 112, 205, 5)), $start->interpolate($end, 0));
        $this->assertEquals(new Solid(new Color(122, 80, 152, 130)), $start->interpolate($end, 32768));
        $this->assertEquals(new Solid(new Color(202, 50, 100, 255)), $start->interpolate($end, 65535));
    }
}
