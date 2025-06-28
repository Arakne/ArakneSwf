<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\ImportAssetsTag;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Test as TestAlias;
use PHPUnit\Framework\TestCase;

class ImportAssetsTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../Fixtures/ground.swf', 27);
        $tag = ImportAssetsTag::read($reader, 1);

        $this->assertSame(1, $tag->version);
        $this->assertSame('clips/gfx/g1.swf', $tag->url);
        $this->assertSame([1 => '[Link_g1-ground]'], $tag->characters);
    }

    #[Test]
    public function readV2()
    {
        $reader = new SwfReader("my/path/to/assets.swf\x00\x01\x00\x03\x00\x02\x00Foo\x00\x03\x00Bar\x00\x04\x00Baz\x00");
        $tag = ImportAssetsTag::read($reader, 2);

        $this->assertSame(2, $tag->version);
        $this->assertSame('my/path/to/assets.swf', $tag->url);
        $this->assertSame([
            2 => 'Foo',
            3 => 'Bar',
            4 => 'Baz',
        ], $tag->characters);
    }
}
