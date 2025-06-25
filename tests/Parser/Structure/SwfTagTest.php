<?php

namespace Arakne\Tests\Swf\Parser\Structure;

use Arakne\Swf\Parser\Structure\SwfTag;
use Arakne\Swf\Parser\Structure\Tag\UnknownTag;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function get_class;
use function iterator_to_array;
use function sprintf;
use function var_dump;

class SwfTagTest extends ParserTestCase
{
    #[Test]
    public function parseUnknownTag()
    {
        $reader = new SwfReader("\x11\x11my unparsed data!");
        [$tag] = iterator_to_array(SwfTag::readAll($reader));

        $parsed = $tag->parse($reader, 5, null);

        $this->assertInstanceOf(UnknownTag::class, $parsed);
        $this->assertSame(68, $parsed->code);
        $this->assertSame('my unparsed data!', $parsed->data);
    }

    #[Test]
    #[TestWith([__DIR__.'/../../Extractor/Fixtures/core/core.swf'])]
    public function coverage(string $file)
    {
        $reader = $this->createReader($file, 21);

        foreach (SwfTag::readAll($reader) as $tag) {
            $this->assertInstanceOf(SwfTag::class, $tag);

            // Ensure that the tag can be parsed
            $parsed = $tag->parse($reader, 9, null);
            $this->assertFalse($parsed instanceof UnknownTag, sprintf('Tag %d should not be unknown', $tag->type));
            $class = $parsed::class;
            $this->assertStringStartsWith('Arakne\Swf\Parser\Structure\Tag\\', $class);
            $this->assertStringEndsWith('Tag', $class);
        }
    }
}
