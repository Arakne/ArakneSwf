<?php

namespace Arakne\Tests\Swf\Parser\Structure\Action;

use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Error\ParserOutOfBoundException;
use Arakne\Swf\Parser\Structure\Action\ActionRecord;
use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function count;
use function file_get_contents;
use function var_dump;

class ActionRecordTest extends ParserTestCase
{
    #[Test]
    public function readCollection()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/simple.swf', 27);

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
        $reader = new SwfReader("\x01\x07", errors: 0);

        $actions = ActionRecord::readCollection($reader, 2);

        $this->assertCount(1, $actions);
        $this->assertSame(Opcode::ActionStop, $actions[0]->opcode);
        $this->assertNull($actions[0]->data);
    }

    #[Test]
    public function readCollectionInvalidOpcode()
    {
        $this->expectException(ParserInvalidDataException::class);
        $this->expectExceptionMessage('Invalid action code "1" at offset 1');

        $reader = new SwfReader("\x01\x07");
        ActionRecord::readCollection($reader, 2);
    }

    #[Test]
    public function tooManyDataRead()
    {
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 2, end: 2)');

        $reader = new SwfReader("\x8CABCD\x00");
        ActionRecord::readCollection($reader, 2);
    }

    #[Test]
    public function tooManyDataReadIgnoreError()
    {
        $reader = new SwfReader("\x8CABCD\x00", errors: 0);
        $actions = ActionRecord::readCollection($reader, 2);

        $this->assertSame(2, $reader->offset);
        $this->assertCount(1, $actions);
        $this->assertSame(Opcode::ActionGoToLabel, $actions[0]->opcode);
        $this->assertSame('', $actions[0]->data);
    }

    #[Test]
    public function endAlreadyReached()
    {
        $reader = new SwfReader('');
        $actions = ActionRecord::readCollection($reader, 0);
        $this->assertCount(0, $actions);
    }

    #[Test]
    public function endOutOfBounds()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 148, end: 147)');

        $reader = $this->createReader(__DIR__.'/../../../Fixtures/simple.swf', 27);
        $reader = $reader->chunk(27, 147);

        ActionRecord::readCollection($reader, 148);
    }

    #[Test]
    public function endOutOfBoundsIgnore()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/simple.swf', 27, errors: 0);
        $reader = $reader->chunk(27, 147);

        $actions = ActionRecord::readCollection($reader, 148);
        $this->assertContainsOnlyInstancesOf(ActionRecord::class, $actions);
        $this->assertCount(11, $actions);
    }
}
