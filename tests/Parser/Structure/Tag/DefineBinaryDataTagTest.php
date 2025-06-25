<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\DefineBinaryDataTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefineBinaryDataTagTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader("\x14\x00\x00\x00\x00\x00binary data");
        $tag = DefineBinaryDataTag::read($reader, 17);

        $this->assertSame(20, $tag->tag);
        $this->assertSame('binary data', $tag->data);
    }
}
