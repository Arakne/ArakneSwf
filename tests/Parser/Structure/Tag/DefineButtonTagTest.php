<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Record\ButtonRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineButtonTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

class DefineButtonTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/swf1/new_theater.swf', 5198);
        $tag = DefineButtonTag::read($reader, 5250);

        $this->assertSame(17, $tag->buttonId);
        $this->assertCount(4, $tag->characters);
        $this->assertContainsOnlyInstancesOf(ButtonRecord::class, $tag->characters);
        $this->assertSame(15, $tag->characters[0]->characterId);
        $this->assertSame(16, $tag->characters[1]->characterId);
        $this->assertSame(15, $tag->characters[2]->characterId);
        $this->assertSame(15, $tag->characters[3]->characterId);
        $this->assertCount(1, $tag->actions);
        $this->assertSame(Opcode::Null, $tag->actions[0]->opcode);
    }
}
