<?php

namespace Arakne\Tests\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Shape\CurvedEdge;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\PathDrawerInterface;
use Arakne\Swf\Extractor\Shape\PathStyle;
use Arakne\Swf\Extractor\Shape\StraightEdge;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    private PathDrawerInterface $stubDrawer;

    protected function setUp(): void
    {
        $this->stubDrawer = new class implements PathDrawerInterface {
            public array $commands = [];

            #[Override]
            public function move(int $x, int $y): void
            {
                $this->commands[] = ['move', $x, $y];
            }

            #[Override]
            public function line(int $toX, int $toY): void
            {
                $this->commands[] = ['line', $toX, $toY];
            }

            #[Override]
            public function curve(int $controlX, int $controlY, int $toX, int $toY): void
            {
                $this->commands[] = ['curve', $controlX, $controlY, $toX, $toY];
            }
        };
    }

    #[Test]
    public function draw()
    {
        $path = new Path(
            [
                new StraightEdge(0, 0, 10, 15),
                new StraightEdge(10, 15, 20, 20),
                new CurvedEdge(20, 20, 30, 25, 40, 20),
            ],
            new PathStyle(),
        );

        $path->draw($this->stubDrawer);

        $this->assertSame([
            ['move', 0, 0],
            ['line', 10, 15],
            ['line', 20, 20],
            ['curve', 30, 25, 40, 20],
        ], $this->stubDrawer->commands);
    }

    #[Test]
    public function drawNotConnected()
    {
        $path = new Path(
            [
                new StraightEdge(0, 0, 10, 15),
                new StraightEdge(12, 22, 0, 42),
            ],
            new PathStyle(),
        );

        $path->draw($this->stubDrawer);

        $this->assertSame([
            ['move', 0, 0],
            ['line', 10, 15],
            ['move', 12, 22],
            ['line', 0, 42],
        ], $this->stubDrawer->commands);
    }

    #[Test]
    public function push()
    {
        $path = new Path(
            [
                new StraightEdge(0, 0, 10, 15),
                new StraightEdge(10, 15, 20, 20),
                new CurvedEdge(20, 20, 30, 25, 40, 20),
            ],
            new PathStyle(),
        );

        $path->push(
            new StraightEdge(40, 20, 50, 25),
            new StraightEdge(50, 25, 60, 30),
        );

        $path->draw($this->stubDrawer);

        $this->assertSame([
            ['move', 0, 0],
            ['line', 10, 15],
            ['line', 20, 20],
            ['curve', 30, 25, 40, 20],
            ['line', 50, 25],
            ['line', 60, 30],
        ], $this->stubDrawer->commands);
    }

    #[Test]
    public function fixShouldReorderEdges()
    {
        $path = new Path(
            [
                new CurvedEdge(20, 20, 15, 15, 10, 10),
                new StraightEdge(10, 15, 20, 20),
                new StraightEdge(10, 10, 0, 0),
                new StraightEdge(0, 0, 10, 15),
            ],
            new PathStyle(),
        );

        $fixed = $path->fix();
        $this->assertNotSame($path, $fixed);
        $this->assertNotEquals($path, $fixed);

        $fixed->draw($this->stubDrawer);

        $this->assertSame([
            ['move', 0, 0],
            ['line', 10, 15],
            ['line', 20, 20],
            ['curve', 15, 15, 10, 10],
            ['line', 0, 0],
        ], $this->stubDrawer->commands);
    }

    #[Test]
    public function fixShouldReverseEdgeForReconnect()
    {
        $path = new Path(
            [
                new StraightEdge(0, 0, 10, 15),
                new StraightEdge(20, 20, 10, 15),
                new CurvedEdge(10, 10, 15, 15, 20, 20),
                new StraightEdge(10, 10, 0, 0),
            ],
            new PathStyle(),
        );

        $fixed = $path->fix();
        $this->assertNotSame($path, $fixed);
        $this->assertNotEquals($path, $fixed);

        $fixed->draw($this->stubDrawer);

        $this->assertSame([
            ['move', 10, 10],
            ['line', 0, 0],
            ['line', 10, 15],
            ['line', 20, 20],
            ['curve', 15, 15, 10, 10],
        ], $this->stubDrawer->commands);
    }

    #[Test]
    public function fixWithDisjunctPath()
    {
        $path = new Path(
            [
                new StraightEdge(10, 15, 0, 0),
                new StraightEdge(0, 0, 10, 10),
                new StraightEdge(10, 10, 10, 15),

                new StraightEdge(30, 25, 20, 20),
                new StraightEdge(20, 20, 30, 30),
                new StraightEdge(30, 30, 30, 25),
            ],
            new PathStyle(),
        );

        $fixed = $path->fix();
        $fixed->draw($this->stubDrawer);

        $this->assertSame([
            ['move', 30, 30],
            ['line', 30, 25],
            ['line', 20, 20],
            ['line', 30, 30],

            ['move', 10, 10],
            ['line', 10, 15],
            ['line', 0, 0],
            ['line', 10, 10],
        ], $this->stubDrawer->commands);
    }
}
