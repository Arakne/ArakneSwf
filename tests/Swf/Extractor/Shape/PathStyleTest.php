<?php

namespace Arakne\Tests\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Shape\PathStyle;
use Arakne\Swf\Parser\Structure\Record\Color;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PathStyleTest extends TestCase
{
    #[Test]
    public function hash()
    {
        $this->assertSame(new PathStyle(null, null, 0)->hash(), new PathStyle(null, null, 0)->hash());
        $this->assertSame(new PathStyle(new Color(125, 14, 0), null, 0)->hash(), new PathStyle(new Color(125, 14, 0), null, 0)->hash());
        $this->assertSame(new PathStyle(null, new Color(125, 14, 0), 0)->hash(), new PathStyle(null, new Color(125, 14, 0), 0)->hash());
        $this->assertSame(new PathStyle(null, new Color(125, 14, 0), 5)->hash(), new PathStyle(null, new Color(125, 14, 0), 5)->hash());
        $this->assertSame(new PathStyle(null, new Color(125, 14, 0, 25), 5)->hash(), new PathStyle(null, new Color(125, 14, 0, 25), 5)->hash());
        $this->assertSame(new PathStyle(null, new Color(125, 14, 0), 5)->hash(), new PathStyle(null, new Color(125, 14, 0, 255), 5)->hash());

        $this->assertNotSame(new PathStyle(null, new Color(125, 14, 0), 0)->hash(), new PathStyle(new Color(125, 14, 0), null, 0)->hash());
        $this->assertNotSame(new PathStyle(new Color(125, 14, 0), null, 0)->hash(), new PathStyle(new Color(124, 14, 0), null, 0)->hash());
        $this->assertNotSame(new PathStyle(new Color(125, 14, 0), null, 0)->hash(), new PathStyle(new Color(125, 15, 0), null, 0)->hash());
        $this->assertNotSame(new PathStyle(new Color(125, 14, 0), null, 0)->hash(), new PathStyle(new Color(125, 14, 1), null, 0)->hash());
        $this->assertNotSame(new PathStyle(null, new Color(125, 14, 0), 0)->hash(), new PathStyle(null, new Color(125, 14, 0), 1)->hash());
        $this->assertNotSame(new PathStyle(null, new Color(125, 14, 0), 0)->hash(), new PathStyle(null, new Color(125, 14, 0), 1)->hash());
        $this->assertNotSame(new PathStyle(null, new Color(125, 14, 0, 25), 5)->hash(), new PathStyle(null, new Color(125, 14, 0, 24), 5)->hash());
    }
}
