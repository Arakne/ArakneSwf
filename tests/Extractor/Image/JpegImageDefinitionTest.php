<?php

namespace Arakne\Tests\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\Image\JpegImageDefinition;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\ImageDataType;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG3Tag;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;

use SimpleXMLElement;

use function base64_decode;
use function in_array;
use function iterator_to_array;
use function substr;

class JpegImageDefinitionTest extends ImageTestCase
{
    #[Test]
    public function characterId()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/core/core.swf');
        $tag = iterator_to_array($swf->tags(DefineBitsJPEG2Tag::ID), false)[0];

        $image = new JpegImageDefinition($tag);
        
        $this->assertSame(540, $image->characterId);
    }

    #[Test]
    public function bounds()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/core/core.swf');
        $tag = iterator_to_array($swf->tags(DefineBitsJPEG2Tag::ID), false)[0];

        $image = new JpegImageDefinition($tag);

        $this->assertEquals(new Rectangle(0, 12000, 0, 2020), $image->bounds());
        $this->assertSame($image->bounds(), $image->bounds());
    }

    #[Test]
    public function framesCount()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/core/core.swf');
        $tag = iterator_to_array($swf->tags(DefineBitsJPEG2Tag::ID), false)[0];

        $image = new JpegImageDefinition($tag);

        $this->assertSame(1, $image->framesCount());
        $this->assertSame(1, $image->framesCount(true));
    }

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
    public function toBase64DataJpeg()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/core/core.swf');
        $tag = iterator_to_array($swf->tags(DefineBitsJPEG2Tag::ID), false)[0];

        $image = new JpegImageDefinition($tag);

        $data = $image->toBase64Data();

        $this->assertStringStartsWith('data:image/jpeg;base64,', $data);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/core/jpeg-540.png', base64_decode(substr($data, 23)), 0.005);
    }

    #[Test]
    public function toBestFormatJpeg()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/core/core.swf');
        $tag = iterator_to_array($swf->tags(DefineBitsJPEG2Tag::ID), false)[0];

        $image = new JpegImageDefinition($tag);

        $data = $image->toBestFormat();

        $this->assertSame(ImageDataType::Jpeg, $data->type);
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/core/jpeg-540.png', $data->data, 0.005);
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
    public function toBase64DataAlphaJpeg()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');
        $image = null;

        /** @var DefineBitsJPEG3Tag $tag */
        foreach ($swf->tags(DefineBitsJPEG3Tag::ID) as $tag) {
            if ($tag->characterId === 507) {
                $image = new JpegImageDefinition($tag);
                break;
            }
        }

        $data = $image->toBase64Data();

        $this->assertStringStartsWith('data:image/png;base64,', $data);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/jpeg-507.png', base64_decode(substr($data, 22)));
    }

    #[Test]
    public function toBestFormatAlphaJpeg()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');
        $image = null;

        /** @var DefineBitsJPEG3Tag $tag */
        foreach ($swf->tags(DefineBitsJPEG3Tag::ID) as $tag) {
            if ($tag->characterId === 507) {
                $image = new JpegImageDefinition($tag);
                break;
            }
        }

        $data = $image->toBestFormat();

        $this->assertSame(ImageDataType::Png, $data->type);
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/jpeg-507.png', $data->data);
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

    #[Test]
    public function transformColors()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        /** @var DefineBitsJPEG3Tag $tag */
        foreach ($swf->tags(DefineBitsJPEG3Tag::ID) as $tag) {
            if ($tag->characterId === 507) {
                $image = new JpegImageDefinition($tag);
                break;
            }
        }

        $transformed = $image->transformColors(new ColorTransform(redMult: 0, blueMult: 0));

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/jpeg-507-green.png', $transformed->toPng());
    }

    #[Test]
    public function transformColorsCache()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        /** @var DefineBitsJPEG3Tag $tag */
        foreach ($swf->tags(DefineBitsJPEG3Tag::ID) as $tag) {
            if ($tag->characterId === 507) {
                $image = new JpegImageDefinition($tag);
                break;
            }
        }

        $transformed = $image->transformColors(new ColorTransform(redMult: 0, blueMult: 0));
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/maps/jpeg-507-green.png', $transformed->toPng());

        $this->assertSame($transformed, $image->transformColors(new ColorTransform(redMult: 0, blueMult: 0)));
        $this->assertNotSame($transformed, $image->transformColors(new ColorTransform(redMult: 0, blueMult: 100)));
    }

    #[Test]
    public function draw()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/maps/0.swf');

        /** @var DefineBitsJPEG3Tag $tag */
        foreach ($swf->tags(DefineBitsJPEG3Tag::ID) as $tag) {
            if ($tag->characterId === 507) {
                $image = new JpegImageDefinition($tag);
                break;
            }
        }

        $svg = new SvgCanvas($image->bounds());
        $image->draw($svg);

        $svg = new SimpleXMLElement($svg->render());

        $this->assertSame('matrix(1, 0, 0, 1, 0, 0)', (string) $svg->g['transform']);
        $this->assertSame($image->toBase64Data(), (string) $svg->g->image->attributes('xlink', true)->href);
    }
}
