<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;
use Arakne\Swf\Parser\Structure\Record\ButtonCondAction;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class ButtonCondActionTest extends ParserTestCase
{
    #[Test]
    public function readCollection()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/core/core.swf', 646131);

        $actions = ButtonCondAction::readCollection($reader, 646162);

        $this->assertCount(1, $actions);
        $this->assertContainsOnlyInstancesOf(ButtonCondAction::class, $actions);

        $this->assertFalse($actions[0]->idleToOverDown);
        $this->assertFalse($actions[0]->outDownToIdle);
        $this->assertFalse($actions[0]->outDownToOverDown);
        $this->assertFalse($actions[0]->overDownToOutDown);
        $this->assertTrue($actions[0]->overDownToOverUp);
        $this->assertFalse($actions[0]->overUpToOverDown);
        $this->assertFalse($actions[0]->overUpToIdle);
        $this->assertFalse($actions[0]->idleToOverUp);
        $this->assertFalse($actions[0]->overDownToIdle);
        $this->assertSame(0, $actions[0]->keyPress);
        $this->assertCount(4, $actions[0]->actions);

        $this->assertEquals(Opcode::ActionPush, $actions[0]->actions[0]->opcode);
        $this->assertEquals([new Value(Type::Double, 0), new Value(Type::String, 'sendCancel')], $actions[0]->actions[0]->data);
        $this->assertEquals(Opcode::ActionCallFunction, $actions[0]->actions[1]->opcode);
        $this->assertEquals(Opcode::ActionPop, $actions[0]->actions[2]->opcode);
        $this->assertEquals(Opcode::Null, $actions[0]->actions[3]->opcode);
    }

    #[Test]
    public function readCollectionInvalidSize()
    {
        $this->expectException(ParserInvalidDataException::class);
        $this->expectExceptionMessage('Invalid ButtonCondAction size: 2');

        $reader = new SwfReader("\x02\x00\x00\x00");
        ButtonCondAction::readCollection($reader, $reader->end);
    }

    #[Test]
    public function readCollectionInvalidSizeIgnoreError()
    {
        $reader = new SwfReader("\x02\x00\x00\x00", errors: 0);
        $this->assertSame([], ButtonCondAction::readCollection($reader, $reader->end));
        $this->assertSame($reader->end, $reader->offset);
    }

    #[Test]
    public function readCollectionShouldStopOnStreamEnd()
    {
        $reader = new SwfReader("\x10\x00\x10\x00\x10\x00\x10\x00\x10\x00\x10\x00\x10\x00\x10\x00\x10\x00\x10\x00\x10\x00\x10\x00\x10\x00\x10\x00\x10\x00", errors: 0);
        $this->assertCount(2, ButtonCondAction::readCollection($reader, $reader->end));
        $this->assertSame($reader->end, $reader->offset);
    }
}
