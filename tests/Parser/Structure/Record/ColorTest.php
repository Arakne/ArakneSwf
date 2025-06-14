<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ColorTest extends TestCase
{
    #[Test]
    public function readRgb()
    {
        $reader = new SwfReader("\x12\x34\x56");
        $color = Color::readRgb($reader);

        $this->assertSame(18, $color->red);
        $this->assertSame(52, $color->green);
        $this->assertSame(86, $color->blue);
        $this->assertNull($color->alpha);
    }

    #[Test]
    public function readRgba()
    {
        $reader = new SwfReader("\x12\x34\x56\x78");
        $color = Color::readRgba($reader);

        $this->assertSame(18, $color->red);
        $this->assertSame(52, $color->green);
        $this->assertSame(86, $color->blue);
        $this->assertSame(120, $color->alpha);
    }
}
