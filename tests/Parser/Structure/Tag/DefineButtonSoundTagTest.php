<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\SoundInfo;
use Arakne\Swf\Parser\Structure\Tag\DefineButtonSoundTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

class DefineButtonSoundTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/swf1/new_theater.swf', 6813);
        $tag = DefineButtonSoundTag::read($reader);

        $this->assertSame(17, $tag->buttonId);
        $this->assertSame(0, $tag->buttonSoundChar0);
        $this->assertNull($tag->buttonSoundInfo0);
        $this->assertSame(0, $tag->buttonSoundChar1);
        $this->assertNull($tag->buttonSoundInfo1);
        $this->assertSame(18, $tag->buttonSoundChar2);
        $this->assertEquals(new SoundInfo(
            syncStop: false,
            syncNoMultiple: false,
            inPoint: null,
            outPoint: null,
            loopCount: null,
            envelopes: []
        ), $tag->buttonSoundInfo2);
        $this->assertSame(0, $tag->buttonSoundChar3);
        $this->assertNull($tag->buttonSoundInfo3);
    }
}
