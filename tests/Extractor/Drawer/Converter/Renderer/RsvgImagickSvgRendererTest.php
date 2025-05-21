<?php

namespace Arakne\Tests\Swf\Extractor\Drawer\Converter\Renderer;

use Arakne\Swf\Extractor\Drawer\Converter\Renderer\RsvgImagickSvgRenderer;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class RsvgImagickSvgRendererTest extends ImageTestCase
{
    protected function setUp(): void
    {
        if (!new RsvgImagickSvgRenderer()->supported()) {
            $this->markTestSkipped('RsvgImagickSvgRenderer is not supported on this system.');
        }
    }

    #[Test]
    public function transparent()
    {
        $svg =
            <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                <circle cx="50" cy="50" r="40" fill="red" />
            </svg>
            SVG;

        $renderer = new RsvgImagickSvgRenderer();
        $image = $renderer->open($svg, 'transparent');
        $image->setFormat('png');

        $this->assertImageStringEqualsImageFile(__DIR__.'/Fixtures/expected-transparent.png', $image->getImageBlob());
    }

    #[Test]
    public function namedColor()
    {
        $svg =
            <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                <circle cx="50" cy="50" r="40" fill="red" />
            </svg>
            SVG;

        $renderer = new RsvgImagickSvgRenderer();
        $image = $renderer->open($svg, 'white');
        $image->setFormat('png');

        $this->assertImageStringEqualsImageFile(__DIR__.'/Fixtures/expected-white.png', $image->getImageBlob());
    }

    #[Test]
    public function hexColor()
    {
        $svg =
            <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                <circle cx="50" cy="50" r="40" fill="red" />
            </svg>
            SVG;

        $renderer = new RsvgImagickSvgRenderer();
        $image = $renderer->open($svg, '#123456');
        $image->setFormat('png');

        $this->assertImageStringEqualsImageFile(__DIR__.'/Fixtures/expected-hex.png', $image->getImageBlob());
    }

    #[Test]
    public function rgbaColor()
    {
        $svg =
            <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                <circle cx="50" cy="50" r="40" fill="red" />
            </svg>
            SVG;

        $renderer = new RsvgImagickSvgRenderer();
        $image = $renderer->open($svg, 'rgba(98, 74, 12, 0.5)');
        $image->setFormat('png');

        $this->assertImageStringEqualsImageFile(__DIR__.'/Fixtures/expected-rgba.png', $image->getImageBlob());
    }

    #[Test]
    public function rgbColor()
    {
        $svg =
            <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                <circle cx="50" cy="50" r="40" fill="red" />
            </svg>
            SVG;

        $renderer = new RsvgImagickSvgRenderer();
        $image = $renderer->open($svg, 'rgb(18, 52, 86)');
        $image->setFormat('png');

        $this->assertImageStringEqualsImageFile(__DIR__.'/Fixtures/expected-hex.png', $image->getImageBlob());
    }

    #[Test]
    public function invalidColor()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('conversion failed: error: Invalid value: The argument \'not a color\' can not be parsed');

        $svg =
            <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                <circle cx="50" cy="50" r="40" fill="red" />
            </svg>
            SVG;

        $renderer = new RsvgImagickSvgRenderer();
        $renderer->open($svg, 'not a color');
    }

    #[Test]
    public function invalidSvg()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Svg conversion failed: Error reading SVG');

        $svg = 'not a valid svg';

        $renderer = new RsvgImagickSvgRenderer();
        $renderer->open($svg, 'transparent');
    }
}
