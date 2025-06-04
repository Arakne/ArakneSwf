<?php

namespace Arakne\Tests\Swf\Extractor\Drawer\Converter;

use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Drawer\Converter\FitSizeResizer;
use Arakne\Swf\Extractor\Drawer\Converter\ImageResizerInterface;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use Imagick;
use PHPUnit\Framework\Attributes\Test;
use SimpleXMLElement;

use function count;
use function explode;
use function fclose;
use function file_get_contents;
use function fwrite;
use function getimagesizefromstring;
use function proc_close;
use function proc_open;
use function stream_get_contents;
use function strlen;
use function strtolower;
use function trim;
use function var_dump;

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
        $baseSvg['viewBox'] = '0 0 39.65 41.95';
        $baseSvg->g['transform'] = 'matrix(1, 0, 0, 1, 29.2, 38.6)';

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
    public function toPngWithOptions()
    {
        $converter = $this->createConverter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $png = $converter->toPng($drawable, 5, ['format' => 'png8', 'bit-depth' => 8, 'compression' => 9]);

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-png8.png',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-png8-rsvg.png',
        ], $png, 0.05);

        $info = getimagesizefromstring($png);

        $this->assertSame('image/png', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
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
    public function toGifSimpleWithOptions()
    {
        $converter = $this->createConverter(backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $gif = $converter->toGif($drawable, 5, ['loop' => 4, 'optimize' => 2]);

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
    public function toWebpSimpleLossless()
    {
        $converter = $this->createConverter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $webp = $converter->toWebp($drawable, 5, ['lossless' => true]);

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
    public function toWebpWithOptions()
    {
        $converter = $this->createConverter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $webp = $converter->toWebp($drawable, 5, ['compression' => 6, 'quality' => 1]);

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/1047/65_frames/65-5.webp',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-inkscape12.webp',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-small-rsvg.webp',
        ], $webp, 0.05);

        $info = getimagesizefromstring($webp);

        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
        $this->assertLessThan(1000, strlen($webp)); // Check that the image is smaller than 1KB
    }

    #[Test]
    public function toWebpWithSize()
    {
        $converter = $this->createConverter(new FitSizeResizer(128, 128));
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $webp = $converter->toWebp($drawable, 5, ['lossless' => true]);

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
    public function toJpegWithOptions()
    {
        $converter = $this->createConverter(backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $jpeg = $converter->toJpeg($drawable, 5, ['quality' => 1, 'sampling' => '422']);

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-small.jpeg',
        ], $jpeg, 0.02);

        $info = getimagesizefromstring($jpeg);

        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
        $this->assertLessThan(400, strlen($jpeg));

        $data = $this->dumpImageData($jpeg);

        if ($data !== null) {
            $this->assertEquals(1, $data['quality']);
            $this->assertContains($data['jpeg'], ['sampling-factor: 2x1,1x1,1x1', 'sampling-factor: 2x2,1x1,1x1']);
        }
    }

    #[Test]
    public function toJpegWithSizeOption()
    {
        $converter = $this->createConverter(backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $jpeg = $converter->toJpeg($drawable, 5, ['size' => '1kb']);

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-1kb.jpeg',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-1kb-rsvg.jpeg',
        ], $jpeg, 0.02);

        $info = getimagesizefromstring($jpeg);

        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(40, $info[0]);
        $this->assertSame(42, $info[1]);
        $this->assertLessThan(1000, strlen($jpeg));
        $this->assertGreaterThan(800, strlen($jpeg));

        $data = $this->dumpImageData($jpeg);

        if ($data !== null) {
            $this->assertGreaterThan(80, $data['quality']);
            $this->assertLessThan(90, $data['quality']);
        }
    }

    #[Test]
    public function toJpegWithSize()
    {
        $converter = $this->createConverter(new FitSizeResizer(128, 128), backgroundColor: '#333333');
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $jpeg = $converter->toJpeg($drawable, 5);

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/1047/65_frames/65-5@128.jpeg',
            __DIR__.'/../../Fixtures/1047/65_frames/65-5-rsvg@128.jpeg',
        ], $jpeg);

        $info = getimagesizefromstring($jpeg);

        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(121, $info[0]);
        $this->assertSame(128, $info[1]);
    }

    #[Test]
    public function toAnimatedGif()
    {
        $converter = $this->createConverter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/mob-leponge/mob-leponge.swf'))->assetByName('walkR');
        $gif = $converter->toAnimatedGif($drawable, 20, true);

        $this->assertAnimatedImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/mob-leponge/walkR.gif',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-rsvg.gif',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-inkscape12.gif',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-inkscape14.gif',
        ], $gif, 0.01);
    }

    #[Test]
    public function toAnimatedGifWithOptions()
    {
        $converter = $this->createConverter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/mob-leponge/mob-leponge.swf'))->assetByName('walkR');
        $gif = $converter->toAnimatedGif($drawable, 20, true, ['loop' => 4, 'optimize' => 2]);

        $this->assertAnimatedImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/mob-leponge/walkR.gif',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-rsvg.gif',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-inkscape12.gif',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-inkscape14.gif',
        ], $gif, 0.01);

        $data = $this->dumpImageData($gif);

        if ($data !== null) {
            $this->assertEquals(4, $data['iterations']);
            $this->assertEquals('5x100', $data['delay']);
        }
    }

    #[Test]
    public function toAnimatedGifWithSize()
    {
        $converter = $this->createConverter(new FitSizeResizer(128, 128));
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/mob-leponge/mob-leponge.swf'))->assetByName('walkR');
        $gif = $converter->toAnimatedGif($drawable, 20, true);

        $this->assertAnimatedImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/mob-leponge/walkR@128.gif',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-rsvg@128.gif',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-inkscape12@128.gif',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-inkscape14@128.gif',
        ], $gif, 0.01);
    }

    #[Test]
    public function toAnimatedWebp()
    {
        $converter = $this->createConverter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/mob-leponge/mob-leponge.swf'))->assetByName('walkR');
        $webp = $converter->toAnimatedWebp($drawable, 20, true, ['lossless' => true]);

        $this->assertAnimatedImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/mob-leponge/walkR.webp',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-rsvg.webp',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-inkscape14.webp',
        ], $webp, 0.01);
    }

    #[Test]
    public function toAnimatedWebpWithOptions()
    {
        $converter = $this->createConverter();
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/mob-leponge/mob-leponge.swf'))->assetByName('walkR');
        $webp = $converter->toAnimatedWebp($drawable, 20, true, ['quality' => 1, 'compression' => 6]);

        $this->assertAnimatedImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/mob-leponge/walkR-small.webp',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-small-rsvg.webp',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-small-rsvg254.webp',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-small-inkscape12.webp',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-small-inkscape14.webp',
        ], $webp, 0.01);

        $this->assertLessThan(8500, strlen($webp)); // Check that the image is smaller than 8KB

        $data = $this->dumpImageData($webp);
        if ($data !== null) {
            $this->assertEquals('5x100', $data['delay']);
        }
    }

    #[Test]
    public function toAnimatedWebpWithSize()
    {
        $converter = $this->createConverter(new FitSizeResizer(128, 128));
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/mob-leponge/mob-leponge.swf'))->assetByName('walkR');
        $webp = $converter->toAnimatedWebp($drawable, 20, true, ['lossless' => true]);

        $this->assertAnimatedImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/mob-leponge/walkR@128.webp',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-rsvg@128.webp',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-rsvg260@128.webp',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-inkscape12@128.webp',
            __DIR__.'/../../Fixtures/mob-leponge/walkR-inkscape14@128.webp',
        ], $webp, 0.02);
    }

    #[Test]
    public function toPngWithHighBlurFilter()
    {
        $converter = $this->createConverter();
        $swf = new SwfFile(__DIR__.'/../../Fixtures/filters/146.swf');
        $extractor = new SwfExtractor($swf);

        $timeline = $extractor->timeline();

        $this->assertImageStringEqualsImageFile([
            __DIR__.'/../../Fixtures/filters/146.png',
            __DIR__.'/../../Fixtures/filters/146-inkscape14.png', // note: Inkscape does not support well feConvolveMatrix, so the render is wrong
            __DIR__.'/../../Fixtures/filters/146-rsvg.png',
        ], $converter->toPng($timeline));
    }

    private function dumpImageData(string $img): ?array
    {
        $proc = @proc_open('identify -verbose -', [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ], $pipes);

        if (!$proc) {
            return null;
        }

        fwrite($pipes[0], $img);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($proc);

        $values = [];

        foreach (explode(PHP_EOL, $output) as $line) {
            $line = trim($line);
            $kv = explode(':', $line, 2);

            if (count($kv) !== 2) {
                continue;
            }

            $key = strtolower(trim($kv[0]));
            $value = trim($kv[1]);

            $values[$key] = $value;
        }

        return $values;
    }
}
