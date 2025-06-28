<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\ExportAssetsTag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExportAssetsTagTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader("\x03\x00\x42\x00test 1\x00\x43\x00test 2\x00\x44\x00test 3\x00");
        $tag = ExportAssetsTag::read($reader);

        $this->assertSame([
            66 => 'test 1',
            67 => 'test 2',
            68 => 'test 3',
        ], $tag->characters);
    }

    #[Test]
    public function readShouldStopAtEndOfTag()
    {
        $reader = new SwfReader("\x15\x00\x42\x00test 1\x00\x43\x00test 2\x00\x44\x00test 3\x00");
        $tag = ExportAssetsTag::read($reader);

        $this->assertSame([
            66 => 'test 1',
            67 => 'test 2',
            68 => 'test 3',
        ], $tag->characters);
    }
}
