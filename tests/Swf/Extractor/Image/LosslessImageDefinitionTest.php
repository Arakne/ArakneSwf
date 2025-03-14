<?php

namespace Arakne\Tests\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Image\LosslessImageDefinition;
use Arakne\Swf\Parser\Structure\Record\ImageBitmapType;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsLosslessTag;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_find;
use function iterator_to_array;

class LosslessImageDefinitionTest extends ImageTestCase
{
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
                $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/homestuck/lossless-8bits-opaque-'.$tag->characterId.'.jpg', $image->toJpeg(100));
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
}
