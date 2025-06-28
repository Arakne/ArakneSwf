<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Error\ParserInvalidDataException;
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

    #[Test]
    public function readInvalidX()
    {
        $this->expectExceptionMessage('Invalid rectangle: xmin (3) is greater than xmax (0)');
        $this->expectException(ParserInvalidDataException::class);

        $reader = new SwfReader("\x1b\x01\x80");
        Rectangle::read($reader);
    }

    #[Test]
    public function readInvalidXIgnoreError()
    {
        $reader = new SwfReader("\x1b\x01\x80", errors: 0);
        $rectangle = Rectangle::read($reader);

        $this->assertSame(0, $rectangle->xmin);
        $this->assertSame(0, $rectangle->xmax);
        $this->assertSame(0, $rectangle->ymin);
        $this->assertSame(3, $rectangle->ymax);
    }

    #[Test]
    public function readInvalidY()
    {
        $this->expectExceptionMessage('Invalid rectangle: ymin (3) is greater than ymax (0)');
        $this->expectException(ParserInvalidDataException::class);

        $reader = new SwfReader("\x18\x6c\x00");
        Rectangle::read($reader);
    }

    #[Test]
    public function readInvalidYIgnoreError()
    {
        $reader = new SwfReader("\x18\x6c\x00", errors: 0);
        $rectangle = Rectangle::read($reader);

        $this->assertSame(0, $rectangle->xmin);
        $this->assertSame(3, $rectangle->xmax);
        $this->assertSame(0, $rectangle->ymin);
        $this->assertSame(0, $rectangle->ymax);
    }
}
