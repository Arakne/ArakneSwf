<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\DefineFont4Tag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefineFont4TagTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader("\x21\x00\x05My font\x00my fond data");
        $tag = DefineFont4Tag::read($reader, $reader->end);

        $this->assertSame(33, $tag->fontId);
        $this->assertFalse($tag->italic);
        $this->assertTrue($tag->bold);
        $this->assertSame('My font', $tag->name);
        $this->assertSame('my fond data', $tag->data);
    }
}
