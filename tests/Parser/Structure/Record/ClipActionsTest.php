<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;
use Arakne\Swf\Parser\Structure\Record\ClipActions;
use Arakne\Swf\Parser\Structure\Record\ClipEventFlags;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class ClipActionsTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/../../../Extractor/Fixtures/core/core.swf'));
        $reader->doUncompress(10000000);
        $reader->skipBytes(529791);

        $clipActions = ClipActions::read($reader, 9);

        $this->assertTrue($clipActions->allEventFlags->has(ClipEventFlags::CONSTRUCT));
        $this->assertFalse($clipActions->allEventFlags->has(ClipEventFlags::DATA));
        $this->assertFalse($clipActions->allEventFlags->has(ClipEventFlags::KEY_PRESS));
        $this->assertFalse($clipActions->allEventFlags->has(ClipEventFlags::KEY_DOWN));

        $this->assertCount(1, $clipActions->records);
        $this->assertTrue($clipActions->records[0]->flags->has(ClipEventFlags::CONSTRUCT));
        $this->assertSame(58, $clipActions->records[0]->size);
        $this->assertCount(7, $clipActions->records[0]->actions);

        $this->assertEquals(Opcode::ActionPush, $clipActions->records[0]->actions[0]->opcode);
        $this->assertEquals([new Value(Type::String, 'enabled'), new Value(Type::Boolean, true)], $clipActions->records[0]->actions[0]->data);
        $this->assertEquals(Opcode::ActionSetVariable, $clipActions->records[0]->actions[1]->opcode);
        $this->assertEquals([new Value(Type::String, 'handCursor'), new Value(Type::Boolean, false)], $clipActions->records[0]->actions[2]->data);
        $this->assertEquals(Opcode::ActionSetVariable, $clipActions->records[0]->actions[3]->opcode);
        $this->assertEquals([new Value(Type::String, 'styleName'), new Value(Type::String, 'default')], $clipActions->records[0]->actions[4]->data);
        $this->assertEquals(Opcode::ActionSetVariable, $clipActions->records[0]->actions[5]->opcode);
        $this->assertEquals(Opcode::Null, $clipActions->records[0]->actions[6]->opcode);
    }
}
