<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\VideoFrameTag;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class VideoFrameTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader("\x12\x00\x64\x00video data");
        $tag = VideoFrameTag::read($reader, 14);

        $this->assertSame(18, $tag->streamId);
        $this->assertSame(100, $tag->frameNum);
        $this->assertSame('video data', $tag->videoData);
    }
}
