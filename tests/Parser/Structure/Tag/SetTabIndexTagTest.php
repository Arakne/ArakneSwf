<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\SetTabIndexTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SetTabIndexTagTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader("\x10\x00\x03\x00");
        $tag = SetTabIndexTag::read($reader);

        $this->assertSame(16, $tag->depth);
        $this->assertSame(3, $tag->tabIndex);
    }
}
