<?php

namespace Arakne\Tests\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\Image\LosslessImageDefinition;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\ImageBitmapType;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsLosslessTag;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use SimpleXMLElement;

use function array_find;
use function base64_decode;
use function iterator_to_array;
use function substr;

class LosslessImageDefinitionTest extends ImageTestCase
{
    #[Test]
    public function characterId()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        $tag = iterator_to_array($swf->tags(DefineBitsLosslessTag::V1_ID), false)[0];
        $image = new LosslessImageDefinition($tag);

        $this->assertSame(534, $image->characterId);
    }

    #[Test]
    public function bounds()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        $tag = iterator_to_array($swf->tags(DefineBitsLosslessTag::V1_ID), false)[0];
        $image = new LosslessImageDefinition($tag);

        $this->assertEquals(new Rectangle(0, 12000, 0, 6900), $image->bounds());
        $this->assertSame($image->bounds(), $image->bounds());
    }

    #[Test]
    public function toPngFullColorWithoutAlpha()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        $tag = iterator_to_array($swf->tags(DefineBitsLosslessTag::V1_ID), false)[0];
        $image = new LosslessImageDefinition($tag);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/lossless-24bits.png', $image->toPng());
    }

    #[Test]
    public function toJpegFullColorWithoutAlpha()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        $tag = iterator_to_array($swf->tags(DefineBitsLosslessTag::V1_ID), false)[0];
        $image = new LosslessImageDefinition($tag);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/lossless-24bits.jpg', $image->toJpeg(100));
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/lossless-24bits.png', $image->toJpeg(100), 0.005);
    }

    #[Test]
    public function toPngFullColorWithAlpha()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        $tag = array_find(iterator_to_array($swf->tags(DefineBitsLosslessTag::V2_ID), false), fn (DefineBitsLosslessTag $tag) => $tag->characterId === 654);
        $image = new LosslessImageDefinition($tag);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/lossless-32bits.png', $image->toPng());
    }

    #[Test]
    public function toJpegFullColorWithAlpha()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        $tag = array_find(iterator_to_array($swf->tags(DefineBitsLosslessTag::V2_ID), false), fn (DefineBitsLosslessTag $tag) => $tag->characterId === 654);
        $image = new LosslessImageDefinition($tag);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/lossless-32bits.jpg', $image->toJpeg(100));
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/lossless-32bits.png', $image->toJpeg(100), 0.01);
    }

    #[Test]
    public function toPng8BitsWithoutAlpha()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/homestuck/00004.swf');

        /** @var DefineBitsLosslessTag $tag */
        foreach ($swf->tags(DefineBitsLosslessTag::V1_ID) as $tag) {
            if ($tag->type() === ImageBitmapType::Opaque8Bit) {
                $image = new LosslessImageDefinition($tag);
                $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/homestuck/lossless-8bits-opaque-'.$tag->characterId.'.png', $image->toPng());
            }
        }
    }

    #[Test]
    public function toJpeg8BitsWithoutAlpha()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/homestuck/00004.swf');

        /** @var DefineBitsLosslessTag $tag */
        foreach ($swf->tags(DefineBitsLosslessTag::V1_ID) as $tag) {
            if ($tag->type() === ImageBitmapType::Opaque8Bit) {
                $image = new LosslessImageDefinition($tag);
                $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/homestuck/lossless-8bits-opaque-'.$tag->characterId.'.jpg', $image->toJpeg(100), 0.004);
                $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/homestuck/lossless-8bits-opaque-'.$tag->characterId.'.png', $image->toJpeg(100), 0.005);
            }
        }
    }

    #[Test]
    public function toPng8BitsWithAlpha()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/homestuck/00004.swf');

        /** @var DefineBitsLosslessTag $tag */
        foreach ($swf->tags(DefineBitsLosslessTag::V2_ID) as $tag) {
            if ($tag->type() === ImageBitmapType::Transparent8Bit) {
                $image = new LosslessImageDefinition($tag);
                $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/homestuck/lossless-8bits-alpha-'.$tag->characterId.'.png', $image->toPng());
            }
        }
    }

    #[Test]
    public function toJpeg8BitsWithAlpha()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/homestuck/00004.swf');

        /** @var DefineBitsLosslessTag $tag */
        foreach ($swf->tags(DefineBitsLosslessTag::V2_ID) as $tag) {
            if ($tag->type() === ImageBitmapType::Transparent8Bit) {
                $image = new LosslessImageDefinition($tag);
                $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/homestuck/lossless-8bits-alpha-'.$tag->characterId.'.png', $image->toJpeg(100), 0.2);
            }
        }
    }

    #[Test]
    public function toBase64Data()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        $tag = array_find(iterator_to_array($swf->tags(DefineBitsLosslessTag::V2_ID), false), fn (DefineBitsLosslessTag $tag) => $tag->characterId === 654);
        $image = new LosslessImageDefinition($tag);

        $data = $image->toBase64Data();

        $this->assertStringStartsWith('data:image/png;base64,', $data);
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/lossless-32bits.png', base64_decode(substr($data, 22)));
    }

    #[Test]
    public function transformColors()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        $tag = array_find(iterator_to_array($swf->tags(DefineBitsLosslessTag::V2_ID), false), fn (DefineBitsLosslessTag $tag) => $tag->characterId === 654);
        $image = new LosslessImageDefinition($tag);

        $transformed = $image->transformColors(new ColorTransform(redMult: 0, greenMult: 0));

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/lossless-32bits-blue.png', $transformed->toPng());
    }

    #[Test]
    public function draw()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        $tag = array_find(iterator_to_array($swf->tags(DefineBitsLosslessTag::V2_ID), false), fn (DefineBitsLosslessTag $tag) => $tag->characterId === 654);
        $image = new LosslessImageDefinition($tag);

        $svg = new SimpleXMLElement($image->draw(new SvgCanvas($image->bounds()))->render());

        $this->assertSame('matrix(1, 0, 0, 1, 0, 0)', (string) $svg->g['transform']);
        $this->assertSame($image->toBase64Data(), (string) $svg->g->image['href']);
    }
}
