<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class RectangleTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../Fixtures/Examples1.swf', 3318);

        $rectangle = Rectangle::read($reader);

        $this->assertSame(-30, $rectangle->xmin);
        $this->assertSame(3530, $rectangle->xmax);
        $this->assertSame(-30, $rectangle->ymin);
        $this->assertSame(970, $rectangle->ymax);
    }
}
