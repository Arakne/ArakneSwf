<?php

namespace Arakne\Tests\Swf\Extractor\Shape\Svg;

use Arakne\Swf\Extractor\Drawer\Svg\SvgPathDrawer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class SvgPathDrawerTest extends TestCase
{
    #[Test]
    public function test()
    {
        $element = new SimpleXMLElement('<path/>');
        $drawer = new SvgPathDrawer($element);

        $drawer->move(10, 20);
        $drawer->line(30, 40);
        $drawer->curve(50, 60, 70, 80);
        $drawer->line(90, 100);
        $drawer->line(110, 110);
        $drawer->draw();

        $this->assertSame('<?xml version="1.0"?>'."\n".'<path d="M0.5 1L1.5 2Q2.5 3 3.5 4L4.5 5L5.5 5.5"/>'."\n", $element->asXML());
    }
}
