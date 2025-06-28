<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\SoundInfo;
use Arakne\Swf\Parser\Structure\Tag\StartSound2Tag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StartSound2TagTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader("Sound class name\x00\x3F\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x00");
        $tag = StartSound2Tag::read($reader);

        $this->assertSame('Sound class name', $tag->soundClassName);
        $this->assertEquals(new SoundInfo(
            syncStop: true,
            syncNoMultiple: true,
            inPoint: 67305985,
            outPoint: 134678021,
            loopCount: 2569,
            envelopes: []
        ), $tag->soundInfo);
    }
}
