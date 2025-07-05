<?php

namespace Arakne\Tests\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\Image\EmptyImage;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\ImageDataType;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;

use SimpleXMLElement;

use function imagecolorat;
use function imagecreatefromstring;
use function imagesx;
use function imagesy;
use function var_dump;

class EmptyImageTest extends ImageTestCase
{
    #[Test]
    public function getters()
    {
        $image = new EmptyImage(42);

        $this->assertSame(42, $image->characterId);
        $this->assertSame(1, $image->framesCount());
        $this->assertSame(1, $image->framesCount(true));
        $this->assertStringStartsWith('data:image/png;base64,', $image->toBase64Data());
        $this->assertEquals(new Rectangle(0, 20, 0, 20), $image->bounds());
        $this->assertSame($image->bounds(), $image->bounds());
        $this->assertSame(ImageDataType::Png, $image->toBestFormat()->type);
        $this->assertSame(EmptyImage::PNG_DATA, $image->toBestFormat()->data);
    }

    #[Test]
    public function toPng()
    {
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/empty.png', new EmptyImage(42)->toPng());

        $gd = imagecreatefromstring(new EmptyImage(42)->toPng());
        $this->assertSame(1, imagesx($gd));
        $this->assertSame(1, imagesy($gd));
        $this->assertSame(0x000000, imagecolorat($gd, 0, 0));
    }

    #[Test]
    public function toJpeg()
    {
        $this->assertImageStringEqualsImageFile(__DIR__.'/../Fixtures/empty.jpeg', new EmptyImage(42)->toJpeg());

        $gd = imagecreatefromstring(new EmptyImage(42)->toJpeg());
        $this->assertSame(1, imagesx($gd));
        $this->assertSame(1, imagesy($gd));
        $this->assertSame(0x000000, imagecolorat($gd, 0, 0));
    }

    #[Test]
    public function transformColor()
    {
        $img = new EmptyImage(42)->transformColors(new ColorTransform(redAdd: 255));

        $gd = imagecreatefromstring($img->toPng());
        $this->assertSame(1, imagesx($gd));
        $this->assertSame(1, imagesy($gd));
        $this->assertSame(0xFF0000, imagecolorat($gd, 0, 0));
    }

    #[Test]
    public function draw()
    {
        $svg = new EmptyImage(42)->draw(new SvgCanvas(new Rectangle(0, 20, 0, 20)))->render();
        $this->assertStringContainsString(new EmptyImage(42)->toBase64Data(), $svg);
    }
}
