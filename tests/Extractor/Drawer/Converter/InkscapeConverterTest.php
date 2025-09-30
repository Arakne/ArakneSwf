<?php

namespace Arakne\Tests\Swf\Extractor\Drawer\Converter;

use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Drawer\Converter\ImageResizerInterface;
use Arakne\Swf\Extractor\Drawer\Converter\Renderer\InkscapeImagickSvgRenderer;
use Arakne\Swf\Extractor\Drawer\Converter\Renderer\RsvgImagickSvgRenderer;

class InkscapeConverterTest extends ConverterTest
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!new InkscapeImagickSvgRenderer()->supported()) {
            $this->markTestSkipped('InkscapeImagickSvgRenderer is not supported on this system.');
        }
    }

    protected function createConverter(?ImageResizerInterface $resizer = null, string $backgroundColor = 'transparent', bool $subpixelStrokeWidth = true): Converter
    {
        return new Converter($resizer, $backgroundColor, new InkscapeImagickSvgRenderer(), $subpixelStrokeWidth);
    }
}
