<?php

namespace Arakne\Tests\Swf\Extractor\Drawer\Converter;

use Arakne\Swf\Extractor\Drawer\Converter\FitSizeResizer;
use Imagick;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class FitSizeResizerTest extends TestCase
{
    #[Test]
    public function scale()
    {
        $resizer = new FitSizeResizer(100, 200);

        $this->assertSame([100.0, 200.0], $resizer->scale(200, 400));
        $this->assertSame([100.0, 200.0], $resizer->scale(100, 200));
        $this->assertSame([100.0, 200.0], $resizer->scale(50, 100));
        $this->assertSame([100.0, 20.0], $resizer->scale(500, 100));
    }

    #[Test]
    public function apply()
    {
        $resizer = new FitSizeResizer(100, 200);
        $img = new Imagick(__DIR__.'/../../Fixtures/sprite-13.svg');

        $resized = $resizer->apply($img, file_get_contents(__DIR__.'/../../Fixtures/sprite-13.svg'));

        $this->assertSame($img, $resized);

        $this->assertSame(98, $img->getImageWidth());
        $this->assertSame(123, $img->getImageHeight());
    }
}
