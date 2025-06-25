<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Tag\DefineButtonCxformTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefineButtonCxformTagTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader("\x17\x00\x00");
        $tag = DefineButtonCxformTag::read($reader);

        $this->assertSame(23, $tag->buttonId);
        $this->assertEquals(new ColorTransform(), $tag->colorTransform);
    }
}
