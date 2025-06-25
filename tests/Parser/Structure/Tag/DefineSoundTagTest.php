<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\SoundInfo;
use Arakne\Swf\Parser\Structure\Tag\DefineSoundTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

use function strlen;

class DefineSoundTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/swf1/new_theater.swf', 5256);
        $tag = DefineSoundTag::read($reader, 6807);

        $this->assertSame(18, $tag->soundId);
        $this->assertSame(1, $tag->soundFormat);
        $this->assertSame(0, $tag->soundRate);
        $this->assertTrue($tag->is16Bits);
        $this->assertFalse($tag->stereo);
        $this->assertSame(4104, $tag->soundSampleCount);
        $this->assertSame(1544, strlen($tag->soundData));
    }
}
