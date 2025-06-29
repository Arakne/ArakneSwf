<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Error\Errors;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Arakne\Swf\Parser\Swf;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class DefineSpriteTagTest extends TestCase
{
    #[Test]
    public function readIgnoreInvalidTag()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/../../Fixtures/1131.swf'));
        $swf = Swf::read(clone $reader);
        $tag = $swf->dictionary[47];

        $reader->skipBytes(8);
        $content = $reader
            ->uncompress()
            ->chunk($tag->offset, $tag->offset + $tag->length)
            ->readBytes($tag->length)
        ;
        $content[11] = "\x42";
        $content[65] = "\x42";
        $content[75] = "\x42";
        $content[202] = "\x00";

        $reader = new SwfReader($content, errors: Errors::IGNORE_INVALID_TAG);
        $tag = DefineSpriteTag::read($reader, 5, $reader->end);

        $this->assertCount(11, $tag->tags);
    }
}
