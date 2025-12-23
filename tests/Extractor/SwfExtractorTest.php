<?php

namespace Arakne\Tests\Swf\Extractor;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\Image\ImageBitsDefinition;
use Arakne\Swf\Extractor\Image\JpegImageDefinition;
use Arakne\Swf\Extractor\Image\LosslessImageDefinition;
use Arakne\Swf\Extractor\MissingCharacter;
use Arakne\Swf\Extractor\MorphShape\MorphShapeDefinition;
use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;

use function array_keys;

class SwfExtractorTest extends ImageTestCase
{
    #[Test]
    public function shapes()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/2.swf'));
        $shapes = $extractor->shapes();

        $this->assertCount(2, $shapes);
        $this->assertSame([1, 2], array_keys($shapes));
        $this->assertContainsOnly(ShapeDefinition::class, $shapes);

        $this->assertSame(1, $shapes[1]->id);
        $this->assertSame(1, $shapes[1]->framesCount());
        $this->assertSame(1, $shapes[1]->framesCount(true));
        $this->assertInstanceOf(DefineShapeTag::class, $shapes[1]->tag);
        $this->assertSame(2, $shapes[2]->id);
        $this->assertSame(1, $shapes[2]->framesCount());
        $this->assertSame(1, $shapes[2]->framesCount(true));
        $this->assertInstanceOf(DefineShapeTag::class, $shapes[2]->tag);

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/2.svg', $shapes[1]->toSvg());
        $this->assertXmlStringEqualsXmlString(
            <<<'SVG'
            <?xml version="1.0"?>
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="25.3px" height="4.8px">
                <g transform="matrix(1, 0, 0, 1, -0.05, 0)">
                    <path fill="#000000" stroke="none" fill-rule="evenodd" d="M25.35 2.4Q25.3 3.4 21.6 4.1L12.7 4.8L3.75 4.1Q0 3.4 0.05 2.4Q0 1.4 3.75 0.7L12.7 0L21.6 0.7Q25.3 1.4 25.35 2.4"/>
                </g>
            </svg>
            SVG,
            $shapes[2]->toSvg()
        );

        $this->assertSame($shapes[1]->shape(), $shapes[1]->shape());
        $this->assertSame($shapes[2]->shape(), $shapes[2]->shape());
    }

    #[Test]
    public function shapesWithoutSubpixelStroke()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/2.swf'));
        $shapes = $extractor->shapes();

        $this->assertCount(2, $shapes);
        $this->assertContainsOnly(ShapeDefinition::class, $shapes);

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/2-no-sp-stroke.svg', $shapes[1]->toSvg(subpixelStrokeWidth: false));
        $this->assertXmlStringEqualsXmlString(
            <<<'SVG'
            <?xml version="1.0"?>
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="25.3px" height="4.8px">
                <g transform="matrix(1, 0, 0, 1, -0.05, 0)">
                    <path fill="#000000" stroke="none" fill-rule="evenodd" d="M25.35 2.4Q25.3 3.4 21.6 4.1L12.7 4.8L3.75 4.1Q0 3.4 0.05 2.4Q0 1.4 3.75 0.7L12.7 0L21.6 0.7Q25.3 1.4 25.35 2.4"/>
                </g>
            </svg>
            SVG,
            $shapes[2]->toSvg(subpixelStrokeWidth: false)
        );
    }

    #[Test]
    public function sprites()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/complex_sprite.swf'));
        $sprites = $extractor->sprites();

        foreach ($sprites as $sprite) {
            $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/sprite-'.$sprite->id.'.svg', $sprite->toSvg());
        }
    }

    #[Test]
    public function characterNotFound()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/complex_sprite.swf'));

        $this->assertInstanceOf(MissingCharacter::class, $extractor->character(10000));
        $this->assertSame(1, $extractor->character(10000)->framesCount());
        $this->assertSame(1, $extractor->character(10000)->framesCount(true));

        $drawer = new SvgCanvas(new Rectangle(0, 0, 0, 0));
        $this->assertSame($drawer, $extractor->character(10000)->draw($drawer));

        $this->assertXmlStringEqualsXmlString(<<<'SVG'
        <?xml version="1.0"?>
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" height="0px" width="0px"/>
        SVG, $drawer->render());
    }

    #[Test]
    public function character0()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/complex_sprite.swf'));
        $this->assertEquals($extractor->timeline(), $extractor->character(0));
    }

    #[Test]
    public function images()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/maps/0.swf'));

        $images = $extractor->images();
        $types = [];

        $this->assertCount(72, $images);

        foreach ($images as $img) {
            $types[$img::class] = $img::class;
        }

        $this->assertContains(ImageBitsDefinition::class, $types);
        $this->assertContains(JpegImageDefinition::class, $types);
        $this->assertContains(LosslessImageDefinition::class, $types);

        $this->assertImageStringEqualsImageFile(__DIR__.'/Fixtures/maps/jpeg-507.png', $images[507]->toPng());
        $this->assertImageStringEqualsImageFile(__DIR__.'/Fixtures/maps/jpeg-525.jpg', $images[525]->toJpeg());
        $this->assertImageStringEqualsImageFile(__DIR__.'/Fixtures/maps/jpeg-525.jpg', $extractor->character(525)->toJpeg());
    }

    #[Test]
    public function spriteWithRasterImage()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/mob-leponge/mob-leponge.swf'));
        $sprite = $extractor->character(29);
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/mob-leponge/sprite-29.svg', $sprite->toSvg());

        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/1597/1597.swf'));
        $sprite = $extractor->character(48);
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1597/sprite-48.svg', $sprite->toSvg());
    }

    #[Test]
    public function spriteWithRasterImageAndColorTransform()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/1047/1047.swf'));

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1047/sprite-3.svg', $extractor->character(3)->toSvg());
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1047/sprite-29.svg', $extractor->character(29)->toSvg());
    }

    #[Test]
    public function byName()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/1047/1047.swf'));

        $staticR = $extractor->byName('staticR');
        $this->assertInstanceOf(SpriteDefinition::class, $staticR);
        $this->assertSame(66, $staticR->id);
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1047/staticR.svg', $staticR->toSvg());

        $staticL = $extractor->byName('staticL');
        $this->assertInstanceOf(SpriteDefinition::class, $staticL);
        $this->assertSame(68, $staticL->id);
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1047/staticL.svg', $staticL->toSvg());
    }

    #[Test]
    public function spriteWithMultipleFrames()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/1047/1047.swf'));
        $sprite = $extractor->character(61);

        $this->assertSame(40, $sprite->framesCount());
        $this->assertSame(40, $sprite->framesCount(true));

        for ($frame = 0; $frame < 40; $frame++) {
            $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1047/61_frames/frame_'.$frame.'.svg', $sprite->toSvg($frame));
        }
    }

    #[Test]
    public function spriteWithMultipleFramesRecursively()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/1047/1047.swf'));
        $sprite = $extractor->byName('anim0R');

        $this->assertSame(1, $sprite->framesCount());
        $this->assertSame(40, $sprite->framesCount(true));

        for ($frame = 0; $frame < 40; $frame++) {
            $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1047/anim0R/frame_'.$frame.'.svg', $sprite->toSvg($frame));
        }
    }

    #[Test]
    public function spriteWithActions()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/1047/1047.swf'));
        $sprite = $extractor->character(5);

        $this->assertCount(1, $sprite->timeline()->frames[0]->actions);
        $this->assertCount(16, $sprite->timeline()->frames[0]->actions[0]->actions);
    }

    #[Test]
    public function exported()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/1047/1047.swf'));

        $this->assertSame([
            'runR' => 29,
            'runL' => 43,
            'bonusR' => 53,
            'bonusL' => 56,
            'anim0R' => 62,
            'anim0L' => 64,
            'staticR' => 66,
            'staticL' => 68,
            'walkL' => 70,
            'walkR' => 72,
            'anim1R' => 77,
            'anim1L' => 79,
            'hitR' => 91,
            'hitL' => 95,
            'dieR' => 97,
            'dieL' => 99,
        ], $extractor->exported());
    }

    #[Test]
    public function timelineSingleFrame()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__ . '/Fixtures/1/1.swf'));
        $timeline = $extractor->timeline(false);

        foreach ($timeline->toSvgAll() as $f => $svg) {
            $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1/frame_'.$f.'.svg', $svg);
        }
    }

    #[Test]
    public function timelineMultipleFrames()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__ . '/Fixtures/homestuck/00004.swf'));
        $timeline = $extractor->timeline();

        foreach ($timeline->toSvgAll() as $f => $svg) {
            $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/homestuck/timeline/frame_'.$f.'.svg', $svg);
        }
    }

    #[Test]
    public function perfIssueApplyColorTransformLazily()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__ . '/Fixtures/1305/1305.swf'));
        $sprite = $extractor->byName('anim0R');

        $svg = $sprite->toSvg();
        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/Fixtures/1305/anim0R.svg', $svg);
    }

    #[Test]
    public function withPlaceObject3Filters()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/62/62.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->timeline(false);

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/62/timeline.svg', $timeline->toSvg());
    }

    #[Test]
    public function withDropShadowFilter()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/54/54.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->timeline(false);

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/54/timeline.svg', $timeline->toSvg());
    }

    #[Test]
    public function perfIssueWithBitmapTransformedMultipleTimes()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/60/60.swf');
        $extractor = new SwfExtractor($swf);

        $sprite = $extractor->timeline();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/60/timeline.svg', $sprite->toSvg());
    }

    #[Test]
    public function bugZeroSizeSpriteWhenLastFrameIsEmpty()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/1058/1058.swf');
        $extractor = new SwfExtractor($swf);
        $sprite = $extractor->byName('anim0R');

        $this->assertEquals(new Rectangle(-817, 1430, -2299, 816), $sprite->bounds());
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1058/anim0R.svg', $sprite->toSvg());
    }

    #[Test]
    public function moveWithNewCharacter()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/1601/1601.swf');
        $extractor = new SwfExtractor($swf);
        $sprite = $extractor->byName('anim0R');
        $this->assertInstanceOf(SpriteDefinition::class, $sprite);

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1601/anim0R/17.svg', $sprite->toSvg(17));
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1601/anim0R/18.svg', $sprite->toSvg(18));

        $innerAnim = $sprite->timeline()->frames[0]->objects[1]->object;

        $this->assertEquals($innerAnim->timeline()->frames[17]->objects[86]->matrix->scaleX, $innerAnim->timeline()->frames[18]->objects[86]->matrix->scaleX);
        $this->assertEquals($innerAnim->timeline()->frames[17]->objects[86]->matrix->scaleY, $innerAnim->timeline()->frames[18]->objects[86]->matrix->scaleY);
        $this->assertEquals($innerAnim->timeline()->frames[17]->objects[86]->matrix->rotateSkew0, $innerAnim->timeline()->frames[18]->objects[86]->matrix->rotateSkew0);
        $this->assertEquals($innerAnim->timeline()->frames[17]->objects[86]->matrix->rotateSkew1, $innerAnim->timeline()->frames[18]->objects[86]->matrix->rotateSkew1);
        $this->assertEqualsWithDelta($innerAnim->timeline()->frames[17]->objects[86]->matrix->translateX, $innerAnim->timeline()->frames[18]->objects[86]->matrix->translateX, 100);
        $this->assertEqualsWithDelta($innerAnim->timeline()->frames[17]->objects[86]->matrix->translateY, $innerAnim->timeline()->frames[18]->objects[86]->matrix->translateY, 100);
        $this->assertEquals(118, $innerAnim->timeline()->frames[17]->objects[86]->object->id);
        $this->assertEquals(119, $innerAnim->timeline()->frames[18]->objects[86]->object->id);
    }

    #[Test]
    public function ignoreFrameObjectWithTooHighBounds()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/1435/1435.swf');
        $extractor = new SwfExtractor($swf);
        $sprite = $extractor->byName('anim0R');

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1435/anim0R.svg', $sprite->toSvg(23));
        $this->assertSame((int) (48.45*20), $sprite->bounds()->width());
        $this->assertSame((int) (35.95*20), $sprite->bounds()->height());
    }

    #[Test]
    public function swf1FileWithPlaceObject()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/swf1/new_theater.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->timeline();

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/swf1/new_theater_frame0.svg', $timeline->toSvg());
    }

    #[Test]
    public function withSingleClipDepth()
    {
        $swf = new SwfFile(__DIR__ . '/Fixtures/mask/189.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->timeline();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/mask/189.svg', $timeline->toSvg());
    }

    #[Test]
    public function withClipDepthOnSprite()
    {
        $swf = new SwfFile(__DIR__ . '/Fixtures/mask/sprite_mask.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->timeline();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/mask/sprite_mask.svg', $timeline->toSvg());
    }

    #[Test]
    public function withNestedClipDepth()
    {
        $swf = new SwfFile(__DIR__ . '/Fixtures/mask/nested_masks.swf');
        $extractor = new SwfExtractor($swf);
        $timeline = $extractor->timeline();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/mask/nested_masks.svg', $timeline->toSvg());

        $swf = new SwfFile(__DIR__ . '/Fixtures/mask/nested_masks2.swf');
        $extractor = new SwfExtractor($swf);
        $timeline = $extractor->timeline();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/mask/nested_masks2.svg', $timeline->toSvg());
    }

    #[Test]
    public function blurFilterTooHigh()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/filters/146.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->timeline();

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/filters/146.svg', $timeline->toSvg());
    }

    #[Test]
    public function releaseIfOutOfMemory()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/filters/146.swf');
        $extractor = new SwfExtractor($swf);
        $extractor->character(1);
        $this->assertNotEmpty(new ReflectionProperty($extractor, 'characters')->getValue($extractor));

        $this->assertFalse($extractor->releaseIfOutOfMemory(1_000_000_000));
        $this->assertNotEmpty(new ReflectionProperty($extractor, 'characters')->getValue($extractor));

        $this->assertTrue($extractor->releaseIfOutOfMemory(1000));
        $this->assertEmpty(new ReflectionProperty($extractor, 'characters')->getValue($extractor));
    }

    #[Test]
    public function colorTransformOnTransparentPixelShouldBeIgnored()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/o3/o3.swf');
        $extractor = new SwfExtractor($swf);
        $sprite = $extractor->character(31);

        $this->assertInstanceOf(SpriteDefinition::class, $sprite);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/Fixtures/o3/sprite-31.svg',
            $sprite->toSvg()
        );
    }

    #[Test]
    public function swf9SymbolClassExport()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/swf9/149.swf');
        $extractor = new SwfExtractor($swf);
        $this->assertCount(1, $extractor->exported());
        $this->assertArrayHasKey('149_fla.MainTimeline', $extractor->exported());
        $this->assertSame(0, $extractor->exported()['149_fla.MainTimeline']);

        $this->assertEquals($extractor->character(0), $extractor->byName('149_fla.MainTimeline'));
    }

    #[Test]
    public function morphShapes()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/homestuck/00004.swf');
        $extractor = new SwfExtractor($swf);
        $morphshapes = $extractor->morphShapes();

        $this->assertCount(6, $morphshapes);
        $this->assertContainsOnly(MorphShapeDefinition::class, $morphshapes);
        $this->assertSame([55, 57, 63, 67, 69, 72], array_keys($morphshapes));
    }
}
