<?php

namespace Arakne\Tests\Swf\Avm;

use Arakne\Swf\Avm\Api\ScriptArray;
use Arakne\Swf\Avm\Api\ScriptObject;
use Arakne\Swf\Avm\Processor;
use Arakne\Swf\Avm\State;
use Arakne\Swf\Parser\Structure\Action\ActionRecord;
use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{
    #[Test]
    public function functionCall()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/func_call.swf');
        $state = new State();
        $processor = new Processor();

        $callingArgs = [];
        $state->functions['myFunction'] = function (string $v, int $o) use(&$callingArgs) {
            $callingArgs = func_get_args();
            return crc32($v) % $o;
        };

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $this->assertSame(['foo', 123], $callingArgs);
        $this->assertSame(23, $state->variables['ret']);
    }

    #[Test]
    public function functionCallDisabled()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/func_call.swf');
        $state = new State();
        $processor = new Processor(allowFunctionCall: false);

        $callingArgs = [];
        $state->functions['myFunction'] = function (string $v, int $o) use(&$callingArgs) {
            $callingArgs = func_get_args();
            return crc32($v) % $o;
        };

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $this->assertSame([], $callingArgs);
        $this->assertNull($state->variables['ret']);
    }

    #[Test]
    public function functionCallInvalidFunction()
    {
        $this->expectExceptionMessage('Unknown function: myFunction');

        $swf = new SwfFile(__DIR__.'/../Fixtures/func_call.swf');
        $state = new State();
        $processor = new Processor();

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }
    }

    #[Test]
    public function undefinedElements()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/undefined.swf');
        $processor = new Processor();
        $state = new State();

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $this->assertCount(7, $state->variables);
        $this->assertNull($state->variables['not_exists']);
        $this->assertNull($state->variables['get_member']);
        $this->assertNull($state->variables['get_member2']);
        $this->assertNull($state->variables['get_member3']);
        $this->assertNull($state->variables['get_member4']);
        $this->assertNull($state->variables['ret']);
        $this->assertEquals(new ScriptObject(), $state->variables['o']);
    }

    #[Test]
    public function methodCallSuccess()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/methods.swf');
        $processor = new Processor();
        $state = new State();
        $obj = new class {
            public $args;

            public function method(string $a, int $b): int
            {
                $this->args = func_get_args();
                return crc32($a) % $b;
            }
        };
        $state->variables['myObject'] = $obj;

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $this->assertSame(['foo', 123], $obj->args);
        $this->assertSame(23, $state->variables['ret']);
    }

    #[Test]
    public function methodCallScriptObjectSuccess()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/methods.swf');
        $processor = new Processor();
        $state = new State();
        $obj = new ScriptObject();
        $obj->method = (function (string $a, int $b) {
            $this->args = func_get_args();
            return crc32($a) % $b;
        })->bindTo($obj);
        $state->variables['myObject'] = $obj;

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $this->assertSame(['foo', 123], $obj->args);
        $this->assertSame(23, $state->variables['ret']);
    }

    #[Test]
    public function methodCallScriptObjectPropertyNotCallable()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/methods.swf');
        $processor = new Processor();
        $state = new State();
        $obj = new ScriptObject();
        $obj->method = false;
        $state->variables['myObject'] = $obj;

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $this->assertNull($state->variables['ret']);
    }

    #[Test]
    public function methodCallNotObject()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/methods.swf');
        $processor = new Processor();
        $state = new State();
        $state->variables['myObject'] = false;

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $this->assertNull($state->variables['ret']);
    }

    #[Test]
    public function methodCallDisabled()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/methods.swf');
        $processor = new Processor(allowFunctionCall: false);
        $state = new State();
        $obj = new class {
            public $args;

            public function method(string $a, int $b): int
            {
                $this->args = func_get_args();
                return crc32($a) % $b;
            }
        };
        $state->variables['myObject'] = $obj;

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $this->assertNull($obj->args);
        $this->assertNull($state->variables['ret']);
    }

    #[Test]
    public function array()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/array.swf');
        $processor = new Processor();
        $state = new State();

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $arr5 = new ScriptArray();
        $arr5[0] = 1;

        $this->assertEquals([
            'arr1' => new ScriptArray(),
            'arr1_length' => 0,
            'arr2' => new ScriptArray(null, null, null, null, null),
            'arr2_length' => 5,
            'arr3' => new ScriptArray(1, 2, 3),
            'arr3_length' => 3,
            'arr4' => new ScriptArray(1, 2, 3, null, null, null, null, null, null, null),
            'arr4_length' => 10,
            'arr5' => $arr5,
            'arr5_length' => 1,
            'arr6' => new ScriptArray(41, 42, 43),
            'arr6_length' => 3,
        ], $state->variables);
    }

    #[Test]
    public function inlineArrayGetMember()
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::String, 'arr')]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::String, 'foo'), new Value(Type::String, 'bar'), new Value(Type::Integer, 2)]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionInitArray, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionSetVariable, 0, null)); // arr = ['foo', 'bar']
        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::String, 'val')]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::String, 'arr')]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionGetVariable, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 1)]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionGetMember, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionSetVariable, 0, null)); // val = arr[1]

        $this->assertSame(['bar', 'foo'], $state->variables['arr']);
        $this->assertSame('foo', $state->variables['val']);
    }

    #[Test]
    public function newObjectClassNotFound()
    {
        $this->expectExceptionMessage('Unknown object type: NotExists');
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 0), new Value(Type::String, 'NotExists')]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionNewObject, 0, null));
    }

    #[Test]
    public function opcodeNotSupported()
    {
        $this->expectExceptionMessage('Unknown action: ActionPreviousFrame {"offset":0,"opcode":5,"length":0,"data":null} Stack: []');
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPreviousFrame, 0, null));
    }
}
