<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\MetadataTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MetadataTagTest extends TestCase
{
    #[Test]
    public function Read()
    {
        $reader = new SwfReader("my metadata\x00");
        $tag = MetadataTag::read($reader);
        $this->assertSame('my metadata', $tag->metadata);
    }
}
