<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\ScriptLimitsTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ScriptLimitsTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../Fixtures/TestFlex.swf', 525);
        $tag = ScriptLimitsTag::read($reader);

        $this->assertSame(1000, $tag->maxRecursionDepth);
        $this->assertSame(60, $tag->scriptTimeoutSeconds);
    }
}
