<?php

namespace Arakne\Tests\Swf\Extractor;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\Image\ImageBitsDefinition;
use Arakne\Swf\Extractor\Image\JpegImageDefinition;
use Arakne\Swf\Extractor\Image\LosslessImageDefinition;
use Arakne\Swf\Extractor\MissingCharacter;
use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function chmod;
use function file_put_contents;

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
        $this->assertInstanceOf(DefineShapeTag::class, $shapes[1]->tag);
        $this->assertSame(2, $shapes[2]->id);
        $this->assertInstanceOf(DefineShapeTag::class, $shapes[2]->tag);

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/2.svg', $shapes[1]->toSvg());
        $this->assertXmlStringEqualsXmlString(
            <<<'SVG'
            <?xml version="1.0"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="25.3px" height="4.8px">
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
    public function sprites()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/complex_sprite.swf'));
        $sprites = $extractor->sprites();

        foreach ($sprites as $sprite) {
            file_put_contents(__DIR__.'/Fixtures/test.svg', $sprite->toSvg());
            chmod(__DIR__.'/Fixtures/test.svg', 0666);
            $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/sprite-'.$sprite->id.'.svg', $sprite->toSvg());
        }
    }

    #[Test]
    public function characterNotFound()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/complex_sprite.swf'));

        $this->assertInstanceOf(MissingCharacter::class, $extractor->character(10000));

        $drawer = new SvgCanvas(new Rectangle(0, 0, 0, 0));
        $this->assertSame($drawer, $extractor->character(10000)->draw($drawer));

        $this->assertXmlStringEqualsXmlString(<<<'SVG'
        <?xml version="1.0"?>
        <svg xmlns="http://www.w3.org/2000/svg" height="0px" width="0px"/>
        SVG, $drawer->render());
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
        file_put_contents(__DIR__.'/Fixtures/1597/sprite-48.svg', $sprite->toSvg());
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1597/sprite-48.svg', $sprite->toSvg());
    }

    #[Test]
    public function spriteWithRasterImageAndColorTransform()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/1047/1047.swf'));

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1047/sprite-3.svg', $extractor->character(3)->toSvg());
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/1047/sprite-29.svg', $extractor->character(29)->toSvg());
    }
}
