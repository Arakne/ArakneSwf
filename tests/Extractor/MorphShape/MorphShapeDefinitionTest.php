<?php

namespace Arakne\Tests\Swf\Extractor\MorphShape;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\MorphShape\MorphShapeDefinition;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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
}
