<?php

namespace Arakne\Tests\Swf\Extractor\Drawer\Converter;

use Arakne\Swf\Extractor\Drawer\Converter\FitSizeResizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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
        $this->assertSame([100.0, 200.0], $resizer->scale(0, 0));
        $this->assertSame([0.0, 200.0], $resizer->scale(0, 50));
        $this->assertSame([100.0, 0.0], $resizer->scale(50, 0));
    }
}
