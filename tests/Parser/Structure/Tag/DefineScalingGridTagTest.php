<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineScalingGridTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefineScalingGridTagTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader("\x51\x00\x16\xE8");
        $tag = DefineScalingGridTag::read($reader);

        $this->assertSame(81, $tag->characterId);
        $this->assertEquals(new Rectangle(
            xmin: -1,
            xmax: 1,
            ymin: -1,
            ymax: 1,
        ), $tag->splitter);
    }
}
