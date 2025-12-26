<?php

namespace Arakne\Tests\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Shape\CurvedEdge;
use Arakne\Swf\Extractor\Shape\PathDrawerInterface;
use Arakne\Swf\Extractor\Shape\StraightEdge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CurvedEdgeTest extends TestCase
{
    #[Test]
    public function test()
    {
        $edge = new CurvedEdge(10, 5, 20, 15, 30, 25);

        $this->assertSame(10, $edge->fromX);
        $this->assertSame(5, $edge->fromY);
        $this->assertSame(20, $edge->controlX);
        $this->assertSame(15, $edge->controlY);
        $this->assertSame(30, $edge->toX);
        $this->assertSame(25, $edge->toY);

        $drawer = $this->createMock(PathDrawerInterface::class);
        $drawer->expects($this->once())->method('curve')->with(20, 15, 30, 25);

        $edge->draw($drawer);

        $this->assertEquals(new CurvedEdge(30, 25, 20, 15, 10, 5), $edge->reverse());
    }

    #[Test]
    public function interpolateWithCurvedEdge()
    {
        $edge = new CurvedEdge(10, 5, 20, 15, 30, 25);
        $other = new CurvedEdge(40, -25, 50, -15, 60, -5);

        $this->assertEquals(new CurvedEdge(10, 5, 20, 15, 30, 25), $edge->interpolate($other, 0));
        $this->assertEquals(new CurvedEdge(25, -10, 35, 0, 45, 9), $edge->interpolate($other, 32768));
        $this->assertEquals(new CurvedEdge(40, -25, 50, -15, 60, -5), $edge->interpolate($other, 65535));
        $this->assertEquals(new CurvedEdge(16, -1, 26, 8, 36, 18), $edge->interpolate($other, 15236));
    }

    #[Test]
    public function interpolateWithStraightEdge()
    {
        $edge = new CurvedEdge(10, 5, 20, 15, 30, 25);
        $other = new StraightEdge(40, -25, 60, -5);

        $this->assertEquals(new CurvedEdge(10, 5, 20, 15, 30, 25), $edge->interpolate($other, 0));
        $this->assertEquals(new CurvedEdge(25, -10, 35, 0, 45, 9), $edge->interpolate($other, 32768));
        $this->assertEquals(new CurvedEdge(40, -25, 50, -15, 60, -5), $edge->interpolate($other, 65535));
        $this->assertEquals(new CurvedEdge(16, -1, 26, 8, 36, 18), $edge->interpolate($other, 15236));
    }
}
