<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\EnableDebuggerTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnableDebuggerTagTest extends TestCase
{
    #[Test]
    public function readV1()
    {
        $reader = new SwfReader("my password\x00");
        $tag = EnableDebuggerTag::read($reader, 1);

        $this->assertSame(1, $tag->version);
        $this->assertSame('my password', $tag->password);
    }

    #[Test]
    public function readV2()
    {
        $reader = new SwfReader("\x00\x00my password\x00");
        $tag = EnableDebuggerTag::read($reader, 2);

        $this->assertSame(2, $tag->version);
        $this->assertSame('my password', $tag->password);
    }
}
