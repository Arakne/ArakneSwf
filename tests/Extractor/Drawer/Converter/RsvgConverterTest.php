<?php

namespace Arakne\Tests\Swf\Extractor\Drawer\Converter;

use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Drawer\Converter\ImageResizerInterface;
use Arakne\Swf\Extractor\Drawer\Converter\Renderer\RsvgImagickSvgRenderer;
use Override;

class RsvgConverterTest extends ConverterTest
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!new RsvgImagickSvgRenderer()->supported()) {
            $this->markTestSkipped('RsvgImagickSvgRenderer is not supported on this system.');
        }
    }

    #[Override]
    protected function createConverter(?ImageResizerInterface $resizer = null, string $backgroundColor = 'transparent', bool $subpixelStrokeWidth = true): Converter
    {
        return new Converter($resizer, $backgroundColor, new RsvgImagickSvgRenderer(), $subpixelStrokeWidth);
    }
}
