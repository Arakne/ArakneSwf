<?php

namespace Arakne\Tests\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Image\ImageBitsDefinition;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsTag;
use Arakne\Swf\Parser\Structure\Tag\JPEGTablesTag;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;

use function iterator_to_array;

class ImageBitsDefinitionTest extends ImageTestCase
{
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
    public function toJpeg()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/g2/g2.swf');
        $jpegTables = iterator_to_array($swf->tags(JPEGTablesTag::ID), false)[0];

        /** @var DefineBitsTag $tag */
        foreach ($swf->tags(DefineBitsTag::ID) as $tag) {
            $image = new ImageBitsDefinition($tag, $jpegTables);
            $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/g2/bits-'.$tag->characterId.'.png', $image->toJpeg());
        }
    }
}
