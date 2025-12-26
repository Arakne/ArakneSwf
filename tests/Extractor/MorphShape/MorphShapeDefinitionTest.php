<?php

namespace Arakne\Tests\Swf\Extractor\MorphShape;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\Modifier\CharacterModifierInterface;
use Arakne\Swf\Extractor\MorphShape\MorphShape;
use Arakne\Swf\Extractor\MorphShape\MorphShapeDefinition;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function range;

class MorphShapeDefinitionTest extends TestCase
{
    #[Test]
    public function getters()
    {
        $swf = new SwfFile(__DIR__ . '/../Fixtures/homestuck/00004.swf');
        $morphShape = $swf->assetById(63);
        $this->assertInstanceOf(MorphShapeDefinition::class, $morphShape);

        $this->assertSame(1, $morphShape->framesCount());
        $this->assertSame(1, $morphShape->framesCount(true));
        $this->assertSame(63, $morphShape->id);
        $this->assertSame(63, $morphShape->tag->characterId);
        $this->assertEquals(new Rectangle(-470, 2201, -1440, 344), $morphShape->bounds());
    }

    #[Test]
    public function boundsWithRatio()
    {
        $swf = new SwfFile(__DIR__ . '/../Fixtures/homestuck/00004.swf');
        $morphShape = $swf->assetById(63);

        $this->assertEquals(new Rectangle(-470, 2201, -1440, 344), $morphShape->withRatio(0)->bounds());
        $this->assertEquals(new Rectangle(-470, 2174, -1440, 344), $morphShape->withRatio(12547)->bounds());
        $this->assertEquals(new Rectangle(-470, 2132, -1442, 344), $morphShape->withRatio(32000)->bounds());
        $this->assertEquals(new Rectangle(-470, 2061, -1445, 344), $morphShape->withRatio(MorphShape::MAX_RATIO)->bounds());
    }

    #[Test]
    public function drawWithRatio()
    {
        $swf = new SwfFile(__DIR__ . '/../Fixtures/homestuck/00004.swf');
        $morphShape = $swf->assetById(63);

        $this->assertInstanceOf(MorphShapeDefinition::class, $morphShape);

        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/homestuck/morphshape_63_frame1.svg',
            $morphShape->draw(new SvgCanvas($morphShape->bounds()))->render()
        );

        $morphShape = $morphShape->withRatio(10000);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/homestuck/morphshape_63_frame10000.svg',
            $morphShape->draw(new SvgCanvas($morphShape->bounds()))->render()
        );

        $morphShape = $morphShape->withRatio(20000);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/homestuck/morphshape_63_frame20000.svg',
            $morphShape->draw(new SvgCanvas($morphShape->bounds()))->render()
        );

        $morphShape = $morphShape->withRatio(30000);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/homestuck/morphshape_63_frame30000.svg',
            $morphShape->draw(new SvgCanvas($morphShape->bounds()))->render()
        );

        $morphShape = $morphShape->withRatio(40000);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/homestuck/morphshape_63_frame40000.svg',
            $morphShape->draw(new SvgCanvas($morphShape->bounds()))->render()
        );

        $morphShape = $morphShape->withRatio(50000);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/homestuck/morphshape_63_frame50000.svg',
            $morphShape->draw(new SvgCanvas($morphShape->bounds()))->render()
        );

        $morphShape = $morphShape->withRatio(65535);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/homestuck/morphshape_63_last_frame.svg',
            $morphShape->draw(new SvgCanvas($morphShape->bounds()))->render()
        );
    }

    #[Test]
    public function morphShapeWithMorphStyle()
    {
        $swf = new SwfFile(__DIR__ . '/../Fixtures/morphshape/morphshape.swf');
        $morphShape = $swf->assetById(1);

        $this->assertInstanceOf(MorphShapeDefinition::class, $morphShape);

        foreach (range(0, 65536, 4096) as $ratio) {
            $morphShape = $morphShape->withRatio($ratio);
            $svg = $morphShape->draw(new SvgCanvas($morphShape->bounds()))->render();
            $this->assertXmlStringEqualsXmlFile(
                __DIR__ . '/../Fixtures/morphshape/morphshape_frame' . $ratio . '.svg',
                $svg
            );
        }
    }

    #[Test]
    public function transformColors()
    {
        $swf = new SwfFile(__DIR__ . '/../Fixtures/morphshape/morphshape.swf');
        $morphShape = $swf->assetById(1);

        $morphShape = $morphShape->withRatio(25000);
        $transformed = $morphShape->transformColors(new ColorTransform(redMult: 512, greenMult: 128, blueMult: 0));

        $this->assertNotEquals($morphShape, $transformed);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/morphshape/morphshape_frame25000_transformed.svg',
            $transformed->draw(new SvgCanvas($morphShape->bounds()))->render()
        );
    }

    #[Test]
    public function modify()
    {
        $modifier = $this->createMock(CharacterModifierInterface::class);

        $swf = new SwfFile(__DIR__ . '/../Fixtures/morphshape/morphshape.swf');
        $morphShape = $swf->assetById(1);
        $transformed = $morphShape->transformColors(new ColorTransform(redMult: 512, greenMult: 128, blueMult: 0));

        $modifier->expects($this->once())->method('applyOnMorphShape')->with($morphShape)->willReturn($transformed);

        $this->assertSame($transformed, $morphShape->modify($modifier));
    }
}
