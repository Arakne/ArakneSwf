<?php

namespace Arakne\Tests\Swf\Extractor\Drawer\Converter;

use Arakne\Swf\Extractor\Drawer\Converter\DrawableFormater;
use Arakne\Swf\Extractor\Drawer\Converter\FitSizeResizer;
use Arakne\Swf\Extractor\Drawer\Converter\ImageFormat;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;

use function getimagesizefromstring;

class DrawableFormaterTest extends ImageTestCase
{
    #[Test]
    public function test()
    {
        $formater = new DrawableFormater(ImageFormat::Png, new FitSizeResizer(128, 128));
        $drawable = (new SwfFile(__DIR__.'/../../Fixtures/1047/1047.swf'))->assetById(65);
        $png = $formater->format($drawable, 5);

        $this->assertImageStringEqualsImageFile(__DIR__.'/../../Fixtures/1047/65_frames/65-5@128.png', $png);

        $info = getimagesizefromstring($png);

        $this->assertSame('image/png', $info['mime']);
        $this->assertSame(121, $info[0]);
        $this->assertSame(127, $info[1]);
        $this->assertSame('png', $formater->extension());
    }
}
