<?php

namespace Arakne\Tests\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Shape\CurvedEdge;
use Arakne\Swf\Extractor\Shape\PathDrawerInterface;
use Arakne\Swf\Extractor\Shape\StraightEdge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StraightEdgeTest extends TestCase
{
    #[Test]
    public function test()
    {
        $edge = new StraightEdge(10, 5, 20, 15);

        $this->assertSame(10, $edge->fromX);
        $this->assertSame(5, $edge->fromY);
        $this->assertSame(20, $edge->toX);
        $this->assertSame(15, $edge->toY);

        $drawer = $this->createMock(PathDrawerInterface::class);
        $drawer->expects($this->once())->method('line')->with(20, 15);

        $edge->draw($drawer);

        $this->assertEquals(new StraightEdge(20, 15, 10, 5), $edge->reverse());
    }

    #[Test]
    public function toCurvedEdge()
    {
        $edge = new StraightEdge(10, 5, 20, 15);
        $curvedEdge = $edge->toCurvedEdge();

        $this->assertSame(10, $curvedEdge->fromX);
        $this->assertSame(5, $curvedEdge->fromY);
        $this->assertSame(15, $curvedEdge->controlX);
        $this->assertSame(10, $curvedEdge->controlY);
        $this->assertSame(20, $curvedEdge->toX);
        $this->assertSame(15, $curvedEdge->toY);
    }

    #[Test]
    public function interpolateWithStraightEdge()
    {
        $edge = new StraightEdge(10, 5, 20, 15);
        $other = new StraightEdge(30, -25, 40, 35);

        $this->assertEquals(new StraightEdge(10, 5, 20, 15), $edge->interpolate($other, 0));
        $this->assertEquals(new StraightEdge(20, -10, 30, 25), $edge->interpolate($other, 32768));
        $this->assertEquals(new StraightEdge(30, -25, 40, 35), $edge->interpolate($other, 65535));
        $this->assertEquals(new StraightEdge(14, -1, 24, 19), $edge->interpolate($other, 15236));
    }

    #[Test]
    public function interpolateWithCurvedEdge()
    {
        $edge = new StraightEdge(10, 5, 20, 15);
        $other = new CurvedEdge(30, -25, 35, 0, 40, 35);

        $this->assertEquals(new CurvedEdge(10, 5, 15, 10, 20, 15), $edge->interpolate($other, 0));
        $this->assertEquals(new CurvedEdge(20, -10, 25, 4, 30, 25), $edge->interpolate($other, 32768));
        $this->assertEquals(new CurvedEdge(30, -25, 35, 0, 40, 35), $edge->interpolate($other, 65535));
        $this->assertEquals(new CurvedEdge(14, -1, 19, 7, 24, 19), $edge->interpolate($other, 15236));
    }
}
