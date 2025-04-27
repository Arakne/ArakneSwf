<?php

namespace Arakne\Tests\Swf\Extractor\Timeline;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FrameTest extends TestCase
{
    #[Test]
    public function getters()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $frame = $extractor->byName('staticR')->timeline()->frames[0];

        $this->assertEquals(new Rectangle(-209, 584, -772, 67), $frame->bounds);
        $this->assertCount(1, $frame->objects);
        $this->assertEmpty($frame->actions);
        $this->assertNull($frame->label);
        $this->assertSame($frame->bounds, $frame->bounds());
        $this->assertSame(1, $frame->framesCount());
        $this->assertSame(18, $frame->framesCount(true));
    }

    #[Test]
    public function gettersWithLabel()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $frame = $extractor->character(65)->timeline()->frames[4];

        $this->assertEquals(new Rectangle(-584, 209, -772, 67), $frame->bounds);
        $this->assertCount(17, $frame->objects);
        $this->assertCount(1, $frame->actions);
        $this->assertSame(Opcode::ActionStop, $frame->actions[0]->actions[0]->opcode);
        $this->assertSame('static', $frame->label);
        $this->assertSame($frame->bounds, $frame->bounds());
        $this->assertSame(1, $frame->framesCount());
        $this->assertSame(1, $frame->framesCount(true));
    }

    #[Test]
    public function draw()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $frame = $extractor->byName('staticR')->timeline()->frames[0];

        $svg = $frame->draw(new SvgCanvas($frame->bounds))->render();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/1047/staticR.svg', $svg);

        $svg = $frame->draw(new SvgCanvas($frame->bounds), 2)->render();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/1047/staticR-2.svg', $svg);

        $svg = $frame->draw(new SvgCanvas($frame->bounds), 4)->render();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/1047/staticR-4.svg', $svg);
        $svg = $frame->draw(new SvgCanvas($frame->bounds), 145)->render();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/1047/staticR-4.svg', $svg);
    }

    #[Test]
    public function transformColors()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $frame = $extractor->byName('staticR')->timeline()->frames[0];

        $transformed = $frame->transformColors(
            new ColorTransform(
                redMult: 256,
                greenMult: 128,
                blueMult: 64,
            )
        );

        $this->assertNotSame($frame, $transformed);
        $this->assertNotEquals($frame, $transformed);

        $svg = $transformed->draw(new SvgCanvas($transformed->bounds))->render();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/1047/staticR-transformed.svg', $svg);
    }

    #[Test]
    public function withBounds()
    {

        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $frame = $extractor->byName('staticR')->timeline()->frames[0];

        $transformed = $frame->withBounds(new Rectangle(0, 100, 0, 100));

        $this->assertNotSame($frame, $transformed);
        $this->assertNotEquals($frame, $transformed);

        $this->assertEquals(new Rectangle(0, 100, 0, 100), $transformed->bounds);

        $svg = $transformed->draw(new SvgCanvas($transformed->bounds))->render();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/1047/staticR-new-bounds.svg', $svg);
    }
}
