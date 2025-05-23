<?php

namespace Arakne\Tests\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Extractor\Shape\PathStyle;
use Arakne\Swf\Parser\Structure\Record\Color;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PathStyleTest extends TestCase
{
    #[Test]
    public function hash()
    {
        $this->assertSame(new PathStyle(null, null, null, 0)->hash(), new PathStyle(null, null, null, 0)->hash());
        $this->assertSame(new PathStyle(new Solid(new Color(125, 14, 0)), null, null, 0)->hash(), new PathStyle(new Solid(new Color(125, 14, 0)), null, null, 0)->hash());
        $this->assertSame(new PathStyle(null, new Color(125, 14, 0), null, 0)->hash(), new PathStyle(null, new Color(125, 14, 0), null, 0)->hash());
        $this->assertSame(new PathStyle(null, new Color(125, 14, 0), null, 5)->hash(), new PathStyle(null, new Color(125, 14, 0), null, 5)->hash());
        $this->assertSame(new PathStyle(null, new Color(125, 14, 0, 25), null, 5)->hash(), new PathStyle(null, new Color(125, 14, 0, 25), null, 5)->hash());
        $this->assertSame(new PathStyle(null, new Color(125, 14, 0), null, 5)->hash(), new PathStyle(null, new Color(125, 14, 0, 255), null, 5)->hash());
        $this->assertSame(new PathStyle(null, null, new Solid(new Color(124, 14, 0)), 5)->hash(), new PathStyle(null, null, new Solid(new Color(124, 14, 0)), 5)->hash());

        $this->assertNotSame(new PathStyle(null, new Color(125, 14, 0), null, 0)->hash(), new PathStyle(new Solid(new Color(125, 14, 0)), null, null, 0)->hash());
        $this->assertNotSame(new PathStyle(new Solid(new Color(125, 14, 0)), null, null, 0)->hash(), new PathStyle(new Solid(new Color(124, 14, 0)), null, null, 0)->hash());
        $this->assertNotSame(new PathStyle(new Solid(new Color(125, 14, 0)), null, null, 0)->hash(), new PathStyle(new Solid(new Color(125, 15, 0)), null, null, 0)->hash());
        $this->assertNotSame(new PathStyle(new Solid(new Color(125, 14, 0)), null, null, 0)->hash(), new PathStyle(new Solid(new Color(125, 14, 1)), null, null, 0)->hash());
        $this->assertNotSame(new PathStyle(null, new Color(125, 14, 0), null, 0)->hash(), new PathStyle(null, new Color(125, 14, 0), null, 1)->hash());
        $this->assertNotSame(new PathStyle(null, new Color(125, 14, 0), null, 0)->hash(), new PathStyle(null, new Color(125, 14, 0), null, 1)->hash());
        $this->assertNotSame(new PathStyle(null, new Color(125, 14, 0, 25), null, 5)->hash(), new PathStyle(null, new Color(125, 14, 0, 24), null, 5)->hash());
        $this->assertNotSame(new PathStyle(null, null, new Solid(new Color(124, 15, 0)), 5)->hash(), new PathStyle(null, null, new Solid(new Color(124, 14, 0)), 5)->hash());
    }
}
