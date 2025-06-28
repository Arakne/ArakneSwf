<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\SoundStreamHeadTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SoundStreamHeadTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../Fixtures/1131.swf', 1633);
        $tag = SoundStreamHeadTag::read($reader, 2);

        $this->assertSame(2, $tag->version);
        $this->assertSame(1, $tag->playbackSoundRate);
        $this->assertSame(1, $tag->playbackSoundSize);
        $this->assertSame(0, $tag->playbackSoundType);
        $this->assertSame(0, $tag->streamSoundCompression);
        $this->assertSame(0, $tag->streamSoundRate);
        $this->assertSame(0, $tag->streamSoundSize);
        $this->assertSame(0, $tag->streamSoundSampleCount);
    }
}
