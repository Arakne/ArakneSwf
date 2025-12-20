<?php

namespace Arakne\Tests\Swf\Extractor\Timeline;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\Image\EmptyImage;
use Arakne\Swf\Extractor\Modifier\AbstractCharacterModifier;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Extractor\Timeline\Frame;
use Arakne\Swf\Extractor\Timeline\FrameObject;
use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;

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

    #[Test]
    public function objectByName()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/complex_sprite.swf');
        $extractor = new SwfExtractor($swf);

        $frame = $extractor->character(13)->timeline()->frames[0];

        $this->assertNull($frame->objectByName('nonexistent'));
        $obj = $frame->objectByName('cheveux');

        $this->assertNotNull($obj);
        $this->assertSame('cheveux', $obj->name);
        $this->assertSame($extractor->character(7), $obj->object);
    }

    #[Test]
    public function addObjectShouldRecomputeBounds()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $frame = $extractor->character(65)->timeline()->frames[0];
        $other = new FrameObject(
            depth: 10,
            object: new EmptyImage(0),
            bounds: new Rectangle(-500, 300, 12, 300),
            matrix: new Matrix(),
        );

        $newFrame = $frame->addObject($other);
        $this->assertNotSame($frame, $newFrame);
        $this->assertEquals(new Rectangle(-584, 300, -772, 300), $newFrame->bounds);
        $this->assertSame($other, $newFrame->objects[10]);

        $sortedKeys = array_keys($newFrame->objects);
        sort($sortedKeys);
        $this->assertSame($sortedKeys, array_keys($newFrame->objects));
    }

    #[Test]
    public function compact()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $tl = $extractor->character(61)->timeline();
        $this->assertEquals($tl->bounds(), $tl->frames[0]->bounds);

        $compactFrame = $tl->frames[0]->compact();
        $this->assertNotSame($tl->frames[0], $compactFrame);
        $this->assertNotEquals($tl->bounds(), $compactFrame->bounds);
        $this->assertEquals(new Rectangle(-461, 212, -752, 67), $compactFrame->bounds);
    }

    #[Test]
    public function modifyOneDepth()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $frame = $extractor->character(65)->timeline()->frames[0];
        $newFrame = $frame->modify(new class extends AbstractCharacterModifier {
            public function applyOnFrame(Frame $frame): Frame
            {
                return $frame->transformColors(new ColorTransform(redMult: 0));
            }

            public function applyOnSprite(SpriteDefinition $sprite): SpriteDefinition
            {
                return $sprite->withAttachment(
                    new Frame(
                        new Rectangle(-500, 300, 12, 300),
                        [
                            new FrameObject(
                                depth: 5,
                                object: new EmptyImage(0),
                                bounds: new Rectangle(-500, 300, 12, 300),
                                matrix: new Matrix(),
                            ),
                        ]
                    ),
                    depth: 20,
                    name: 'modifier-added-object',
                );
            }
        }, 1);

        $this->assertNotEquals($frame, $newFrame);
        $this->assertNotEquals($frame->bounds(), $newFrame->bounds());
        $this->assertEquals(new Rectangle(-636, 555, -803, 372), $newFrame->bounds);

        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/1047/frame-modify.svg',
            $newFrame->draw(new SvgCanvas($newFrame->bounds()))->render()
        );
    }

    #[Test]
    public function modifyNoDepth()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $frame = $extractor->character(65)->timeline()->frames[0];
        $newFrame = $frame->modify(new class extends AbstractCharacterModifier {
            public function applyOnFrame(Frame $frame, ?int $frameNumber = null): Frame
            {
                return $frame->transformColors(new ColorTransform(redMult: 0));
            }

            public function applyOnSprite(SpriteDefinition $sprite, ?int $frame = null): SpriteDefinition
            {
                return $sprite->withAttachment(
                    new Frame(
                        new Rectangle(-500, 300, 12, 300),
                        [
                            new FrameObject(
                                depth: 5,
                                object: new EmptyImage(0),
                                bounds: new Rectangle(-500, 300, 12, 300),
                                matrix: new Matrix(),
                            ),
                        ]
                    ),
                    depth: 20,
                    name: 'modifier-added-object',
                );
            }
        }, 0);

        $this->assertNotEquals($frame, $newFrame);
        $this->assertEquals($frame->bounds(), $newFrame->bounds());

        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/1047/frame-modify-only-frame.svg',
            $newFrame->draw(new SvgCanvas($newFrame->bounds()))->render()
        );
    }

    #[Test]
    public function modifyWithoutModificationShouldReturnSameInstance()
    {

        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $frame = $extractor->character(65)->timeline()->frames[0];
        $this->assertSame($frame, $frame->modify(new class extends AbstractCharacterModifier {}));
    }
}
