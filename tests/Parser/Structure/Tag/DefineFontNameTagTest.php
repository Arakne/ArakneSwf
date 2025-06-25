<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\DefineFontNameTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefineFontNameTagTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader("\x35\x00Font name\x00Font copyright\x00");
        $tag = DefineFontNameTag::read($reader);

        $this->assertSame(53, $tag->fontId);
        $this->assertSame('Font name', $tag->fontName);
        $this->assertSame('Font copyright', $tag->fontCopyright);
    }
}
