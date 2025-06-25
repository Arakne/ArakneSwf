<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Action\ActionRecord;
use Arakne\Swf\Parser\Structure\Tag\DoInitActionTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

class DoInitActionTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/sunAndShadow.swf', 459);
        $tag = DoInitActionTag::read($reader, 1565);

        $this->assertSame(6, $tag->spriteId);
        $this->assertCount(194, $tag->actions);
        $this->assertContainsOnlyInstancesOf(ActionRecord::class, $tag->actions);
    }
}
