<?php

namespace Arakne\Tests\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\Image\ImageBitsDefinition;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsTag;
use Arakne\Swf\Parser\Structure\Tag\JPEGTablesTag;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;

use function base64_decode;
use function iterator_to_array;
use function substr;

class ImageBitsDefinitionTest extends ImageTestCase
{
    #[Test]
    public function characterId()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/g2/g2.swf');
        [$jpegTables, $tag] = iterator_to_array($swf->tags(JPEGTablesTag::ID, DefineBitsTag::ID), false);
        $image = new ImageBitsDefinition($tag, $jpegTables);

        $this->assertSame(244, $image->characterId);
    }

    #[Test]
    public function bounds()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/g2/g2.swf');
        [$jpegTables, $tag] = iterator_to_array($swf->tags(JPEGTablesTag::ID, DefineBitsTag::ID), false);

        $image = new ImageBitsDefinition($tag, $jpegTables);

        $this->assertEquals(
            new Rectangle(0, 15200, 0, 9100),
            $image->bounds()
        );

        $this->assertSame($image->bounds(), $image->bounds());
    }

    #[Test]
    public function framesCount()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/g2/g2.swf');
        [$jpegTables, $tag] = iterator_to_array($swf->tags(JPEGTablesTag::ID, DefineBitsTag::ID), false);

        $image = new ImageBitsDefinition($tag, $jpegTables);

        $this->assertSame(1, $image->framesCount());
        $this->assertSame(1, $image->framesCount(true));
    }

    #[Test]
    public function toPng()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/g2/g2.swf');
        $jpegTables = iterator_to_array($swf->tags(JPEGTablesTag::ID), false)[0];

        /** @var DefineBitsTag $tag */
        foreach ($swf->tags(DefineBitsTag::ID) as $tag) {
            $image = new ImageBitsDefinition($tag, $jpegTables);
            $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/g2/bits-'.$tag->characterId.'.png', $image->toPng());
        }
    }

    #[Test]
    public function toBase64Data()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/g2/g2.swf');
        $jpegTables = iterator_to_array($swf->tags(JPEGTablesTag::ID), false)[0];

        /** @var DefineBitsTag $tag */
        foreach ($swf->tags(DefineBitsTag::ID) as $tag) {
            $image = new ImageBitsDefinition($tag, $jpegTables);
            $data = $image->toBase64Data();

            $this->assertStringStartsWith('data:image/jpeg;base64,', $data);

            $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/g2/bits-'.$tag->characterId.'.png', base64_decode(substr($data, 23)), 0.005);
        }
    }

    #[Test]
    public function toJpeg()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/g2/g2.swf');
        $jpegTables = iterator_to_array($swf->tags(JPEGTablesTag::ID), false)[0];

        /** @var DefineBitsTag $tag */
        foreach ($swf->tags(DefineBitsTag::ID) as $tag) {
            $image = new ImageBitsDefinition($tag, $jpegTables);
            $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/g2/bits-'.$tag->characterId.'.png', $image->toJpeg(), 0.005);
        }
    }

    #[Test]
    public function transformColors()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/g2/g2.swf');
        [$jpegTables, $tag] = iterator_to_array($swf->tags(JPEGTablesTag::ID, DefineBitsTag::ID), false);

        $image = new ImageBitsDefinition($tag, $jpegTables);
        $transformed = $image->transformColors(new ColorTransform(greenMult: 0, blueMult: 0));

        $this->assertSame($image->bounds(), $transformed->bounds());
        $this->assertSame($image->characterId, $transformed->characterId);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/g2/bits-244-red.png', $transformed->toPng());
    }

    #[Test]
    public function draw()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/g2/g2.swf');
        $jpegTables = iterator_to_array($swf->tags(JPEGTablesTag::ID), false)[0];

        /** @var DefineBitsTag $tag */
        foreach ($swf->tags(DefineBitsTag::ID) as $tag) {
            $image = new ImageBitsDefinition($tag, $jpegTables);
            $drawer = new SvgCanvas($image->bounds());
            $image->draw($drawer);

            $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/g2/bits-'.$tag->characterId.'.svg', $drawer->render());
        }
    }
}
