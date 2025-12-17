<?php

namespace Arakne\Tests\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\Image\JpegImageDefinition;
use Arakne\Swf\Extractor\Image\TransformedImage;
use Arakne\Swf\Extractor\Modifier\CharacterModifierInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\ImageDataType;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG3Tag;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use SimpleXMLElement;

use function base64_decode;
use function file_get_contents;
use function substr;

class TransformedImageTest extends ImageTestCase
{
    public const string BASE_IMAGE_PNG = __DIR__.'/../Fixtures/g2/bits-283.png';
    public const int BASE_IMAGE_WIDTH = 800;
    public const int BASE_IMAGE_HEIGHT = 600;

    #[
        Test,
        TestWith([new ColorTransform(), self::BASE_IMAGE_PNG]),
        TestWith([new ColorTransform(redMult: 0), __DIR__.'/../Fixtures/g2/bits-283-no-red.png']),
        TestWith([new ColorTransform(greenMult: 0), __DIR__.'/../Fixtures/g2/bits-283-no-green.png']),
        TestWith([new ColorTransform(blueMult: 0), __DIR__.'/../Fixtures/g2/bits-283-no-blue.png']),
        TestWith([new ColorTransform(alphaMult: 128), __DIR__.'/../Fixtures/g2/bits-283-alpha50.png']),
        TestWith([new ColorTransform(alphaMult: 64), __DIR__.'/../Fixtures/g2/bits-283-alpha25.png']),
        TestWith([new ColorTransform(alphaMult: 192), __DIR__.'/../Fixtures/g2/bits-283-alpha75.png']),
        TestWith([new ColorTransform(redMult: 128, greenMult: 128, blueMult: 128), __DIR__.'/../Fixtures/g2/bits-283-darken50.png']),
        TestWith([new ColorTransform(
            redMult: 200,
            greenMult: 75,
            blueMult: 256,
            redAdd: 50,
            greenAdd: -50,
            blueAdd: 100,
        ), __DIR__.'/../Fixtures/g2/bits-283-complex-matrix.png']),
    ]
    public function fromPng(ColorTransform $colorTransform, string $expectedFile)
    {
        $image = TransformedImage::createFromPng(
            1,
            new Rectangle(0, self::BASE_IMAGE_WIDTH, 0, self::BASE_IMAGE_HEIGHT),
            $colorTransform,
            file_get_contents(self::BASE_IMAGE_PNG)
        );

        $this->assertImageStringEqualsImageFile($expectedFile, $image->toPng());
        $this->assertSame(1, $image->characterId);
        $this->assertEquals(new Rectangle(0, self::BASE_IMAGE_WIDTH, 0, self::BASE_IMAGE_HEIGHT), $image->bounds());
        $this->assertSame(1, $image->framesCount());
        $this->assertSame(1, $image->framesCount(true));
    }

    #[Test]
    public function fromPngTransparentPixelShouldBeIgnored()
    {
        $image = TransformedImage::createFromPng(
            1,
            new Rectangle(0, 173, 0, 83),
            new ColorTransform(
                redMult: 64,
                greenMult: 128,
                blueMult: 192,
                alphaMult: 256,
                redAdd: 50,
                greenAdd: -50,
                blueAdd: 100,
                alphaAdd: 75,
            ),
            file_get_contents(__DIR__ . '/../Fixtures/g2/20.png')
        );

        $this->assertImageStringEqualsImageFile(__DIR__ . '/../Fixtures/g2/20-transformed.png', $image->toPng());
        $this->assertSame(1, $image->characterId);
        $this->assertEquals(new Rectangle(0, 173, 0, 83), $image->bounds());
        $this->assertSame(1, $image->framesCount());
        $this->assertSame(1, $image->framesCount(true));
    }

    #[
        Test,
        TestWith([new ColorTransform(), __DIR__.'/../Fixtures/maps/jpeg-525.jpg']),
        TestWith([new ColorTransform(redMult: 0), __DIR__.'/../Fixtures/maps/jpeg-525-no-red.png']),
        TestWith([new ColorTransform(alphaMult: 128), __DIR__.'/../Fixtures/maps/jpeg-525-alpha50.png']),
    ]
    public function fromJpeg(ColorTransform $colorTransform, string $expectedFile)
    {
        $image = TransformedImage::createFromJpeg(
            1,
            new Rectangle(0, 600, 0, 345),
            $colorTransform,
            file_get_contents(__DIR__.'/../Fixtures/maps/jpeg-525.jpg')
        );

        $this->assertImageStringEqualsImageFile($expectedFile, $image->toPng());
        $this->assertSame(1, $image->characterId);
        $this->assertEquals(new Rectangle(0, 600, 0, 345), $image->bounds());
    }

    #[Test]
    public function toBase64Data()
    {
        $image = TransformedImage::createFromPng(
            1,
            new Rectangle(0, self::BASE_IMAGE_WIDTH, 0, self::BASE_IMAGE_HEIGHT),
            new ColorTransform(redMult: 0),
            file_get_contents(self::BASE_IMAGE_PNG)
        );

        $this->assertStringStartsWith('data:image/png;base64,', $image->toBase64Data());
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/g2/bits-283-no-red.png', base64_decode(substr($image->toBase64Data(), 22)));
    }

    #[Test]
    public function toBestFormat()
    {
        $image = TransformedImage::createFromPng(
            1,
            new Rectangle(0, self::BASE_IMAGE_WIDTH, 0, self::BASE_IMAGE_HEIGHT),
            new ColorTransform(redMult: 0),
            file_get_contents(self::BASE_IMAGE_PNG)
        );

        $data = $image->toBestFormat();
        $this->assertSame(ImageDataType::Png, $data->type);
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/g2/bits-283-no-red.png', $data->data);
    }

    #[Test]
    public function toJpeg()
    {
        $image = TransformedImage::createFromPng(
            1,
            new Rectangle(0, self::BASE_IMAGE_WIDTH, 0, self::BASE_IMAGE_HEIGHT),
            new ColorTransform(redMult: 0),
            file_get_contents(self::BASE_IMAGE_PNG)
        );

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/g2/bits-283-no-red.png', $image->toJpeg(100), .016);
    }

    #[Test]
    public function transformColors()
    {
        $image = TransformedImage::createFromPng(
            1,
            new Rectangle(0, self::BASE_IMAGE_WIDTH, 0, self::BASE_IMAGE_HEIGHT),
            new ColorTransform(redMult: 0),
            file_get_contents(self::BASE_IMAGE_PNG)
        );

        $transformed = $image->transformColors(new ColorTransform(alphaMult: 128));

        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/g2/bits-283-no-red-alpha50.png', $transformed->toPng());
    }

    #[Test]
    public function draw()
    {
        $image = TransformedImage::createFromPng(
            1,
            new Rectangle(0, self::BASE_IMAGE_WIDTH, 0, self::BASE_IMAGE_HEIGHT),
            new ColorTransform(redMult: 0),
            file_get_contents(self::BASE_IMAGE_PNG)
        );

        $svg = new SimpleXMLElement($image->draw(new SvgCanvas($image->bounds()))->render());

        $this->assertSame('matrix(1, 0, 0, 1, 0, 0)', (string) $svg->g['transform']);
        $this->assertSame($image->toBase64Data(), (string) $svg->g->image->attributes('xlink', true)->href);
    }

    #[Test]
    public function modify()
    {
        $image = TransformedImage::createFromPng(
            1,
            new Rectangle(0, self::BASE_IMAGE_WIDTH, 0, self::BASE_IMAGE_HEIGHT),
            new ColorTransform(redMult: 0),
            file_get_contents(self::BASE_IMAGE_PNG)
        );
        $modifier = $this->createMock(CharacterModifierInterface::class);
        $newImage = clone $image;

        $modifier->expects($this->once())->method('applyOnImage')->with($image)->willReturn($newImage);

        $this->assertSame($newImage, $image->modify($modifier));
    }
}
