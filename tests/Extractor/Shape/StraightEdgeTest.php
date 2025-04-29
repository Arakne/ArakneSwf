<?php

namespace Arakne\Tests\Swf\Extractor\Shape;

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
}
