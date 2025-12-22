<?php

namespace Arakne\Tests\Swf\Extractor\Timeline;

use Arakne\Swf\Extractor\Modifier\AbstractCharacterModifier;
use Arakne\Swf\Extractor\Modifier\CharacterModifierInterface;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Extractor\Timeline\Frame;
use Arakne\Swf\Extractor\Timeline\Timeline;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Matrix;
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
    public function toSvgAllWithoutSubpixelStroke()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();

        foreach ($timeline->toSvgAll(false) as $f => $svg) {
            $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../Fixtures/1047/65_frames/65-'.$f.'-no-sp-stroke.svg', $svg);
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

    #[Test]
    public function withAttachment()
    {
        $timeline = new SwfFile(__DIR__.'/../Fixtures/1/1.swf')->timeline(false);
        $other = new SwfFile(__DIR__.'/../Fixtures/62/62.swf')->timeline();

        $combined = $timeline->withAttachment($other, depth: 10, name: 'attached');

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/1/with-attachment.svg', $combined->toSvg());
        $this->assertEquals(new Rectangle(-565, 11186, -1236, 16118), $combined->bounds());

        foreach ($combined->frames as $frame) {
            $obj = $frame->objectByName('attached');
            $this->assertNotNull($obj);
            $this->assertSame($other, $obj->object);
        }
    }

    #[Test]
    public function modifyFrames()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $modified = $timeline->modify(new class extends AbstractCharacterModifier {
            public function applyOnTimeline(Timeline $timeline): Timeline
            {
                return $timeline->transformColors(new ColorTransform(greenMult: 0));
            }

            public function applyOnFrame(Frame $frame): Frame
            {
                return new Frame(
                    bounds: $frame->bounds->transform(new Matrix(2.0, 3.0)),
                    objects: $frame->objects,
                    actions: $frame->actions,
                    label: $frame->label,
                );
            }
        }, 1);

        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/1047/65_frames/modified.svg',
            $modified->toSvg()
        );

        $this->assertNotEquals($timeline->bounds, $modified->bounds);
        $this->assertEquals($timeline->bounds->transform(new Matrix(2.0, 3.0)), $modified->bounds);
    }

    #[Test]
    public function modifyOnlyCurrent()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $newTimeline = clone $timeline;

        $modifier = $this->createMock(CharacterModifierInterface::class);
        $modifier->expects($this->once())->method('applyOnTimeline')->with($timeline)->willReturn($newTimeline);
        $modifier->expects($this->never())->method('applyOnFrame');

        $modified = $timeline->modify($modifier, 0);
        $this->assertSame($newTimeline, $modified);
    }

    #[Test]
    public function modifyOneDepth()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $newTimeline = clone $timeline;

        $modifier = $this->createMock(CharacterModifierInterface::class);
        $modifier->expects($this->once())->method('applyOnTimeline')->with($timeline)->willReturn($newTimeline);
        $modifier->expects($this->exactly(18))->method('applyOnFrame')->willReturnArgument(0);

        $modified = $timeline->modify($modifier, 1);
        $this->assertSame($newTimeline, $modified);
    }

    #[Test]
    public function modifyAllDepths()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $newTimeline = clone $timeline;

        $modifier = $this->createMock(CharacterModifierInterface::class);
        $modifier->expects($this->exactly(325))->method('applyOnTimeline')->willReturnCallback(function (Timeline $param) use ($timeline, $newTimeline) {
            if ($param == $timeline) {
                return $newTimeline;
            }
            return $param;
        });
        $modifier->expects($this->exactly(342))->method('applyOnFrame')->willReturnArgument(0);
        $modifier->expects($this->exactly(324))->method('applyOnShape')->willReturnArgument(0);
        $modifier->expects($this->exactly(324))->method('applyOnSprite')->willReturnArgument(0);

        $modified = $timeline->modify($modifier);
        $this->assertSame($newTimeline, $modified);
    }

    #[Test]
    public function modifyWithoutModificationShouldReturnSameInstance()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $this->assertSame($timeline, $timeline->modify(new class extends AbstractCharacterModifier {}));
    }

    #[Test]
    public function frameByLabel()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();

        $this->assertSame($timeline->frames[4], $timeline->frameByLabel('static'));
        $this->assertSame('static', $timeline->frameByLabel('static')->label);

        $this->assertNull($timeline->frameByLabel('not_found'));
    }

    #[Test]
    public function keepFrameByLabelFound()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $newTimeline = $timeline->keepFrameByLabel('static');

        $this->assertCount(1, $newTimeline->frames);
        $this->assertSame('static', $newTimeline->frames[0]->label);
        $this->assertSame($timeline->frames[4], $newTimeline->frames[0]);
        $this->assertSame($timeline->bounds(), $newTimeline->bounds());
    }

    #[Test]
    public function keepFrameByLabelNotFound()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $newTimeline = $timeline->keepFrameByLabel('not_found');

        $this->assertCount(1, $newTimeline->frames);
        $this->assertNull($newTimeline->frames[0]->label);
        $this->assertSame($timeline->frames[0], $newTimeline->frames[0]);
        $this->assertSame($timeline->bounds(), $newTimeline->bounds());
    }

    #[Test]
    public function keepFrameByNumberFound()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $newTimeline = $timeline->keepFrameByNumber(5);

        $this->assertCount(1, $newTimeline->frames);
        $this->assertSame('static', $newTimeline->frames[0]->label);
        $this->assertSame($timeline->frames[4], $newTimeline->frames[0]);
        $this->assertSame($timeline->bounds(), $newTimeline->bounds());
    }

    #[Test]
    public function keepFrameByNumberTooLow()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $newTimeline = $timeline->keepFrameByNumber(0);

        $this->assertCount(1, $newTimeline->frames);
        $this->assertNull($newTimeline->frames[0]->label);
        $this->assertSame($timeline->frames[0], $newTimeline->frames[0]);
        $this->assertSame($timeline->bounds(), $newTimeline->bounds());
    }

    #[Test]
    public function keepFrameByNumberTooHigh()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->character(65)->timeline();
        $newTimeline = $timeline->keepFrameByNumber(42);

        $this->assertCount(1, $newTimeline->frames);
        $this->assertNull($newTimeline->frames[0]->label);
        $this->assertSame($timeline->frames[17], $newTimeline->frames[0]);
        $this->assertSame($timeline->bounds(), $newTimeline->bounds());
    }
}
