<?php

namespace Arakne\Tests\Swf\Extractor\Drawer\Converter;

use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Drawer\Converter\FitSizeResizer;
use Arakne\Swf\Extractor\Drawer\Converter\ImageResizerInterface;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;
use SimpleXMLElement;

use function file_get_contents;
use function getimagesizefromstring;

class ConverterTest extends ImageTestCase
{
    protected function createConverter(?ImageResizerInterface $resizer = null, string $backgroundColor = 'transparent'): Converter
    {
        return new Converter($resizer, $backgroundColor);
    }

    #[Test]
    public function toSvgSimple()
    {
        $converter = $this->createConverter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $svg = $converter->toSvg($drawable, 5);

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5.svg', $svg);
    }

    #[Test]
    public function toSvgWithResize()
    {
        $converter = $this->createConverter(new FitSizeResizer(128, 128));
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
        $converter = $this->createConverter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $png = $converter->toPng($drawable, 5);

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/1047/65_frames/65-5.png',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-inkscape12.png',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-inkscape14.png',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-rsvg.png',
        ], $png, 0.05);

        $info = getimagesizefromstring($png);

        $this->assertSame('image/png', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
    }

    #[Test]
    public function toPngWithSize()
    {
        $converter = $this->createConverter(new FitSizeResizer(128, 128));
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $png = $converter->toPng($drawable, 5);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5@128.png', $png);

        $info = getimagesizefromstring($png);

        $this->assertSame('image/png', $info['mime']);
        $this->assertSame(121, $info[0]);
        $this->assertSame(128, $info[1]);
    }

    #[Test]
    public function toGifSimple()
    {
        $converter = $this->createConverter(backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $gif = $converter->toGif($drawable, 5);

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/1047/65_frames/65-5.gif',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-rsvg.gif',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-inkscape12.gif',
        ], $gif, 0.02);

        $info = getimagesizefromstring($gif);

        $this->assertSame('image/gif', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
    }

    #[Test]
    public function toGifWithSize()
    {
        $converter = $this->createConverter(new FitSizeResizer(128, 128), backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $gif = $converter->toGif($drawable, 5);

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/1047/65_frames/65-5@128.gif',
        ], $gif);

        $info = getimagesizefromstring($gif);

        $this->assertSame('image/gif', $info['mime']);
        $this->assertSame(121, $info[0]);
        $this->assertSame(128, $info[1]);
    }

    #[Test]
    public function toWebpSimple()
    {
        $converter = $this->createConverter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $webp = $converter->toWebp($drawable, 5);

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/1047/65_frames/65-5.webp',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-inkscape12.webp',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-rsvg.webp',
        ], $webp, 0.05);

        $info = getimagesizefromstring($webp);

        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
    }

    #[Test]
    public function toWebpWithSize()
    {
        $converter = $this->createConverter(new FitSizeResizer(128, 128));
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $webp = $converter->toWebp($drawable, 5);

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/1047/65_frames/65-5@128.webp',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-rsvg@128.webp',
        ], $webp, 0.005);

        $info = getimagesizefromstring($webp);

        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(121, $info[0]);
        $this->assertSame(128, $info[1]);
    }

    #[Test]
    public function toJpegSimple()
    {
        $converter = $this->createConverter(backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $jpeg = $converter->toJpeg($drawable, 5);

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/1047/65_frames/65-5.jpeg',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-rsvg.jpeg',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-inkscape12.jpeg',
        ], $jpeg, 0.02);

        $info = getimagesizefromstring($jpeg);

        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
    }

    #[Test]
    public function toJpegWithSize()
    {
        $converter = $this->createConverter(new FitSizeResizer(128, 128), backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $jpeg = $converter->toJpeg($drawable, 5);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5@128.jpeg', $jpeg);

        $info = getimagesizefromstring($jpeg);

        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(121, $info[0]);
        $this->assertSame(128, $info[1]);
    }
}
