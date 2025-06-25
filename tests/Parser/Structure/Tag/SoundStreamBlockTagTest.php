<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\SoundStreamBlockTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SoundStreamBlockTagTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader('my sound data');
        $tag = SoundStreamBlockTag::read($reader, $reader->end);

        $this->assertSame('my sound data', $tag->soundData);
    }
}
