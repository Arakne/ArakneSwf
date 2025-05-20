<?php

namespace Arakne\Tests\Swf\Extractor\Drawer\Converter;

use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Drawer\Converter\FitSizeResizer;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use SimpleXMLElement;

use function file_get_contents;
use function file_put_contents;
use function getimagesizefromstring;
use function imagecreatefromstring;
use function imagesx;

class ConverterTest extends ImageTestCase
{
    #[Test]
    public function toSvgSimple()
    {
        $converter = new Converter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $svg = $converter->toSvg($drawable, 5);

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5.svg', $svg);
    }

    #[Test]
    public function toSvgWithResize()
    {
        $converter = new Converter(new FitSizeResizer(128, 128));
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $svg = $converter->toSvg($drawable, 5);

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5@128.svg', $svg);

        $baseSvg = new SimpleXMLElement(file_get_contents(__DIR__.'/../../Fixtures/1047/65_frames/65-5.svg'));
        $baseSvg['width'] = '120.9821215733';
        $baseSvg['height'] = '128';
        $baseSvg['viewBox'] = '0 0 121 128';
        $baseSvg->g['transform'] = 'scale(3.051251, 3.051251) matrix(1, 0, 0, 1, 29.2, 38.6)';

        $this->assertXmlStringEqualsXmlString($baseSvg->asXML(), $svg);
    }

    #[Test]
    public function toPngSimple()
    {
        $converter = new Converter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $png = $converter->toPng($drawable, 5);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5.png', $png);

        $info = getimagesizefromstring($png);

        $this->assertSame('image/png', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
    }

    #[Test]
    public function toPngWithSize()
    {
        $converter = new Converter(new FitSizeResizer(128, 128));
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $png = $converter->toPng($drawable, 5);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5@128.png', $png);

        $info = getimagesizefromstring($png);

        $this->assertSame('image/png', $info['mime']);
        $this->assertSame(121, $info[0]);
        $this->assertSame(127, $info[1]);
    }

    #[Test]
    public function toGifSimple()
    {
        $converter = new Converter(backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $gif = $converter->toGif($drawable, 5);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5.gif', $gif);

        $info = getimagesizefromstring($gif);

        $this->assertSame('image/gif', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
    }

    #[Test]
    public function toGifWithSize()
    {
        $converter = new Converter(new FitSizeResizer(128, 128), backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $gif = $converter->toGif($drawable, 5);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5@128.gif', $gif);

        $info = getimagesizefromstring($gif);

        $this->assertSame('image/gif', $info['mime']);
        $this->assertSame(121, $info[0]);
        $this->assertSame(127, $info[1]);
    }

    #[Test]
    public function toWebpSimple()
    {
        $converter = new Converter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $webp = $converter->toWebp($drawable, 5);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5.webp', $webp);

        $info = getimagesizefromstring($webp);

        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
    }

    #[Test]
    public function toWebpWithSize()
    {
        $converter = new Converter(new FitSizeResizer(128, 128));
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $webp = $converter->toWebp($drawable, 5);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5@128.webp', $webp);

        $info = getimagesizefromstring($webp);

        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(121, $info[0]);
        $this->assertSame(127, $info[1]);
    }

    #[Test]
    public function toJpegSimple()
    {
        $converter = new Converter(backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $jpeg = $converter->toJpeg($drawable, 5);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5.jpeg', $jpeg);

        $info = getimagesizefromstring($jpeg);

        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
    }

    #[Test]
    public function toJpegWithSize()
    {
        $converter = new Converter(new FitSizeResizer(128, 128), backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $jpeg = $converter->toJpeg($drawable, 5);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5@128.jpeg', $jpeg);

        $info = getimagesizefromstring($jpeg);

        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(121, $info[0]);
        $this->assertSame(127, $info[1]);
    }
}
