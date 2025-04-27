<?php

namespace Arakne\Tests\Swf\Extractor\Timeline;

use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_put_contents;

class TimelineTest extends TestCase
{
    #[Test]
    public function getters()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();

        $this->assertEquals(new Rectangle(-584, 209, -772, 67), $timeline->bounds);
        $this->assertSame($timeline->bounds, $timeline->bounds());
        $this->assertCount(18, $timeline->frames);
        $this->assertSame(18, $timeline->framesCount());
        $this->assertSame(18, $timeline->framesCount(true));
    }

    #[Test]
    public function toSvg()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../Fixtures/1047/65_frames/65-0.svg', $timeline->toSvg(0));
        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../Fixtures/1047/65_frames/65-10.svg', $timeline->toSvg(10));
        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../Fixtures/1047/65_frames/65-17.svg', $timeline->toSvg(17));
        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../Fixtures/1047/65_frames/65-17.svg', $timeline->toSvg(250));
    }

    #[Test]
    public function toSvgAll()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();

        foreach ($timeline->toSvgAll() as $f => $svg) {
            $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../Fixtures/1047/65_frames/65-'.$f.'.svg', $svg);
        }
    }

    #[Test]
    public function transformColors()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $transformed = $timeline->transformColors(new ColorTransform(
            redMult: 0,
            greenMult: 256,
            blueMult: 256,
            alphaAdd: 256,
        ));

        foreach ($transformed->toSvgAll() as $f => $svg) {
            $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../Fixtures/1047/65_frames/transformed-'.$f.'.svg', $svg);
        }
    }

    #[Test]
    public function withBounds()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $transformed = $timeline->withBounds(new Rectangle(0, 200, 0, 200));

        foreach ($transformed->frames as $frame) {
            $this->assertEquals(new Rectangle(0, 200, 0, 200), $frame->bounds);
        }

        foreach ($transformed->toSvgAll() as $f => $svg) {
            $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../Fixtures/1047/65_frames/new-bounds-'.$f.'.svg', $svg);
        }
    }
}
