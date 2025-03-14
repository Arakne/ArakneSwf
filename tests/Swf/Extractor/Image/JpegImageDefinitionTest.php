<?php

namespace Arakne\Tests\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Image\JpegImageDefinition;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG3Tag;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;

use function iterator_to_array;

class JpegImageDefinitionTest extends ImageTestCase
{
    #[Test]
    public function toPngOpaqueJpeg()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/core/core.swf');
        $tag = iterator_to_array($swf->tags(DefineBitsJPEG2Tag::ID), false)[0];

        $image = new JpegImageDefinition($tag);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/core/jpeg-540.png', $image->toPng());
    }

    #[Test]
    public function toJpegOpaqueJpeg()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/core/core.swf');
        $tag = iterator_to_array($swf->tags(DefineBitsJPEG2Tag::ID), false)[0];

        $image = new JpegImageDefinition($tag);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/core/jpeg-540.png', $image->toJpeg(), 0.005);
    }

    #[Test]
    public function toPngAlphaJpeg()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');
        $ids = [507, 669];

        /** @var DefineBitsJPEG3Tag $tag */
        foreach ($swf->tags(DefineBitsJPEG3Tag::ID) as $tag) {
            if (in_array($tag->characterId, $ids)) {
                $images[] = new JpegImageDefinition($tag);
            }
        }

        $this->assertCount(2, $images);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/jpeg-507.png', $images[0]->toPng());
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/jpeg-669.png', $images[1]->toPng());
    }

    #[Test]
    public function toJpegAlphaJpeg()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');
        $ids = [507, 669];

        /** @var DefineBitsJPEG3Tag $tag */
        foreach ($swf->tags(DefineBitsJPEG3Tag::ID) as $tag) {
            if (in_array($tag->characterId, $ids)) {
                $images[] = new JpegImageDefinition($tag);
            }
        }

        $this->assertCount(2, $images);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/jpeg-507.jpg', $images[0]->toJpeg());
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/jpeg-669.jpg', $images[1]->toJpeg());
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/jpeg-669.png', $images[1]->toJpeg(), 0.1);
    }
}
