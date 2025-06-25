<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\ProtectTag;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProtectTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/sunAndShadow.swf', 32);
        $tag = ProtectTag::read($reader, 32);
        $this->assertNull($tag->password);
    }

    #[Test]
    public function readWithPassword()
    {
        $reader = new SwfReader("My password\0");
        $tag = ProtectTag::read($reader, $reader->end);

        $this->assertSame('My password', $tag->password);
    }
}
