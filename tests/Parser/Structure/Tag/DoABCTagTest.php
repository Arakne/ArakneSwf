<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\DoABCTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DoABCTagTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader("\x12\x34\x56\x78My script name\x00the ABC data");
        $tag = DoABCTag::read($reader, 31);

        $this->assertSame(0x78563412, $tag->flags);
        $this->assertSame('My script name', $tag->name);
        $this->assertSame('the ABC data', $tag->data);
    }
}
