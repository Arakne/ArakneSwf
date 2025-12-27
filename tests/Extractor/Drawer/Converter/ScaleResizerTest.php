<?php

namespace Arakne\Tests\Swf\Extractor\Drawer\Converter;

use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Drawer\Converter\ScaleResizer;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ScaleResizerTest extends TestCase
{
    #[Test]
    public function scale()
    {
        $resizer = new ScaleResizer(2.4);

        $this->assertSame([240.0, 480.0], $resizer->scale(100.0, 200.0));
    }

    #[Test]
    public function functionalWithConverter()
    {
        $converter = new Converter(new ScaleResizer(1.2), subpixelStrokeWidth: false);
        $swf = new SwfFile(__DIR__ . '/../../Fixtures/mob-leponge/mob-leponge.swf');
        $sprite = $swf->assetByName('staticR');

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../../Fixtures/mob-leponge/staticRx1.2.svg', $converter->toSvg($sprite));
    }
}
