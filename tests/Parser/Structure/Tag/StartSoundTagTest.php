<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\SoundEnvelope;
use Arakne\Swf\Parser\Structure\Record\SoundInfo;
use Arakne\Swf\Parser\Structure\Tag\StartSoundTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

class StartSoundTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__. '/../../../Extractor/Fixtures/swf1/new_theater.swf', 7227);
        $tag = StartSoundTag::read($reader);

        $this->assertSame(18, $tag->soundId);
        $this->assertEquals(new SoundInfo(
            syncStop: false,
            syncNoMultiple: false,
            inPoint: null,
            outPoint: null,
            loopCount: null,
            envelopes: [
                new SoundEnvelope(
                    pos44: 0,
                    leftLevel: 32768,
                    rightLevel: 0,
                ),
            ]
        ), $tag->soundInfo);
    }
}
