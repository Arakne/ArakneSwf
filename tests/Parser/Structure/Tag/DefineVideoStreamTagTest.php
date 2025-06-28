<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\DefineVideoStreamTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefineVideoStreamTagTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader("\x29\x00\x0C\x00\x40\x01\xF0\x00\x05\x02");
        $tag = DefineVideoStreamTag::read($reader);

        $this->assertSame(41, $tag->characterId);
        $this->assertSame(12, $tag->numFrames);
        $this->assertSame(320, $tag->width);
        $this->assertSame(240, $tag->height);
        $this->assertSame(2, $tag->deblocking);
        $this->assertTrue($tag->smoothing);
        $this->assertSame(2, $tag->codecId);
    }
}
