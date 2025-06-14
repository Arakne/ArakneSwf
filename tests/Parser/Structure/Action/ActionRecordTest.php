<?php

namespace Arakne\Tests\Swf\Parser\Structure\Action;

use Arakne\Swf\Parser\Structure\Action\ActionRecord;
use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function count;
use function file_get_contents;
use function var_dump;

class ActionRecordTest extends TestCase
{
    #[Test]
    public function readCollection()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/../../../Fixtures/simple.swf'));
        $reader->doUncompress(152);
        $reader->skipBytes(27);

        $actions = ActionRecord::readCollection($reader, 147);

        $this->assertContainsOnlyInstancesOf(ActionRecord::class, $actions);
        $this->assertCount(11, $actions);

        $this->assertSame(Opcode::ActionConstantPool, $actions[0]->opcode);
        $this->assertSame(['simple_int', 'simple_string', 'abc', 'simple_float', 'simple_bool', 'simple_null'], $actions[0]->data);
        $this->assertSame(Opcode::ActionPush, $actions[1]->opcode);
        $this->assertEquals([new Value(Type::Constant8, 0), new Value(Type::Integer, 123)], $actions[1]->data);
        $this->assertSame(Opcode::ActionSetVariable, $actions[2]->opcode);
        $this->assertNull($actions[2]->data);
    }

    #[Test]
    public function readCollectionInvalidOpcodeShouldBeIgnored()
    {
        $reader = new SwfReader("\x01\x07");

        $actions = ActionRecord::readCollection($reader, 2);

        $this->assertCount(1, $actions);
        $this->assertSame(Opcode::ActionStop, $actions[0]->opcode);
        $this->assertNull($actions[0]->data);
    }

    #[Test]
    public function tooManyDataRead()
    {
        $this->expectExceptionMessage('Too many bytes read: offset=6, end=2');

        $reader = new SwfReader("\x8CABCD\x00");
        ActionRecord::readCollection($reader, 2);
    }
}
