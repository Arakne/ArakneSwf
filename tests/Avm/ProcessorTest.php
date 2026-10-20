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
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function provideAdd(): array
    {
        return [
            'integers' => [new Value(Type::Integer, 3), new Value(Type::Integer, 4), 7],
            'float and integer' => [new Value(Type::Double, 1.5), new Value(Type::Integer, 2), 3.5],
            'numeric strings' => [new Value(Type::String, '3'), new Value(Type::String, '4.5'), 7.5],
            'non numeric string' => [new Value(Type::String, 'abc'), new Value(Type::Integer, 5), 5.0],
            'boolean and null' => [new Value(Type::Boolean, true), new Value(Type::Null, null), 1.0],
        ];
    }

    #[Test]
    #[DataProvider('provideAdd')]
    public function add(Value $a, Value $b, mixed $expected)
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [$a, $b]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionAdd, 0, null));

        $this->assertSame([$expected], $state->stack);
    }

    public static function provideAdd2(): array
    {
        return [
            'integers' => [new Value(Type::Integer, 3), new Value(Type::Integer, 4), 7],
            'float and integer' => [new Value(Type::Double, 1.5), new Value(Type::Integer, 2), 3.5],
            'strings' => [new Value(Type::String, 'foo'), new Value(Type::String, 'bar'), 'foobar'],
            'numeric string and number' => [new Value(Type::String, '3'), new Value(Type::Integer, 4), '34'],
            'number and numeric string' => [new Value(Type::Integer, 4), new Value(Type::String, '3'), '43'],
            'float and string' => [new Value(Type::Double, 1.5), new Value(Type::String, 'x'), '1.5x'],
            'boolean and number' => [new Value(Type::Boolean, true), new Value(Type::Integer, 1), 2],
            'null and string' => [new Value(Type::Null, null), new Value(Type::String, 'a'), 'nulla'],
            'boolean and string' => [new Value(Type::Boolean, true), new Value(Type::String, 'a'), 'truea'],
        ];
    }

    #[Test]
    #[DataProvider('provideAdd2')]
    public function add2(Value $a, Value $b, mixed $expected)
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [$a, $b]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionAdd2, 0, null));

        $this->assertSame([$expected], $state->stack);
    }

    public static function provideAdd2NaN(): array
    {
        return [
            'null and number' => [new Value(Type::Null, null), new Value(Type::Integer, 1)],
            'number and null' => [new Value(Type::Integer, 1), new Value(Type::Null, null)],
        ];
    }

    #[Test]
    #[DataProvider('provideAdd2NaN')]
    public function add2NaN(Value $a, Value $b)
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [$a, $b]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionAdd2, 0, null));

        $this->assertCount(1, $state->stack);
        $this->assertNan($state->stack[0]);
    }

    #[Test]
    public function add2WithObject()
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 0)]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionInitObject, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 1)]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionAdd2, 0, null)); // {} + 1

        $this->assertSame(['[object Object]1'], $state->stack);
    }

    #[Test]
    public function add2WithInlineArray()
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 2), new Value(Type::Integer, 1), new Value(Type::Integer, 2)]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionInitArray, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 3), new Value(Type::Integer, 1)]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionInitArray, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionAdd2, 0, null)); // [1, 2] + [3]

        $this->assertSame(['1,23'], $state->stack);
    }

    #[Test]
    public function add2WithScriptArray()
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 3), new Value(Type::Integer, 2), new Value(Type::Integer, 1), new Value(Type::Integer, 3), new Value(Type::String, 'Array')]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionNewObject, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::String, '')]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionAdd2, 0, null)); // new Array(1, 2, 3) + ''

        $this->assertSame(['1,2,3'], $state->stack);
    }

    #[Test]
    public function add2WithCustomToString()
    {
        $state = new State();
        $processor = new Processor();
        $state->variables['obj'] = new ScriptObject(['toString' => fn () => 'foo']);

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::String, 'obj')]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionGetVariable, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::String, '!')]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionAdd2, 0, null)); // obj + '!'

        $this->assertSame(['foo!'], $state->stack);
    }

    public static function provideStringAdd(): array
    {
        return [
            'strings' => [new Value(Type::String, 'foo'), new Value(Type::String, 'bar'), 'foobar'],
            'integer and string' => [new Value(Type::Integer, 12), new Value(Type::String, 'a'), '12a'],
            'string and float' => [new Value(Type::String, 'x'), new Value(Type::Double, 1.5), 'x1.5'],
            'integers' => [new Value(Type::Integer, 3), new Value(Type::Integer, 4), '34'],
            'booleans' => [new Value(Type::Boolean, true), new Value(Type::Boolean, false), 'truefalse'],
        ];
    }

    #[Test]
    #[DataProvider('provideStringAdd')]
    public function stringAdd(Value $a, Value $b, string $expected)
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [$a, $b]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionStringAdd, 0, null));

        $this->assertSame([$expected], $state->stack);
    }

    #[Test]
    public function stringAddWithObjectAndArray()
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 0)]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionInitObject, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 2), new Value(Type::Integer, 1), new Value(Type::Integer, 2)]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionInitArray, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionStringAdd, 0, null)); // {} add [1, 2]

        $this->assertSame(['[object Object]1,2'], $state->stack);
    }

    public static function provideSubtract(): array
    {
        return [
            'integers' => [new Value(Type::Integer, 10), new Value(Type::Integer, 3), 7],
            'operand order' => [new Value(Type::Integer, 3), new Value(Type::Integer, 10), -7],
            'float and integer' => [new Value(Type::Double, 3.5), new Value(Type::Integer, 1), 2.5],
            'numeric strings' => [new Value(Type::String, '10'), new Value(Type::String, '4.5'), 5.5],
            'numeric string and integer' => [new Value(Type::String, '10'), new Value(Type::Integer, 3), 7.0],
            'boolean and integer' => [new Value(Type::Boolean, true), new Value(Type::Integer, 1), 0],
        ];
    }

    #[Test]
    #[DataProvider('provideSubtract')]
    public function subtract(Value $a, Value $b, mixed $expected)
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [$a, $b]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionSubtract, 0, null));

        $this->assertSame([$expected], $state->stack);
    }

    public static function provideSubtractNaN(): array
    {
        return [
            'non numeric string' => [new Value(Type::String, 'abc'), new Value(Type::Integer, 5)],
            'integer and non numeric string' => [new Value(Type::Integer, 5), new Value(Type::String, 'abc')],
            'null and integer' => [new Value(Type::Null, null), new Value(Type::Integer, 1)],
        ];
    }

    #[Test]
    #[DataProvider('provideSubtractNaN')]
    public function subtractNaN(Value $a, Value $b)
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [$a, $b]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionSubtract, 0, null));

        $this->assertCount(1, $state->stack);
        $this->assertNan($state->stack[0]);
    }

    #[Test]
    public function subtractWithInlineArray()
    {
        $state = new State();
        $processor = new Processor();

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 5), new Value(Type::Integer, 1)]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionInitArray, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 1)]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionSubtract, 0, null)); // [5] - 1

        $this->assertSame([4.0], $state->stack);
    }

    #[Test]
    public function subtractWithCustomToString()
    {
        $state = new State();
        $processor = new Processor();
        $state->variables['obj'] = new ScriptObject(['toString' => fn () => '10']);

        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::String, 'obj')]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionGetVariable, 0, null));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionPush, 0, [new Value(Type::Integer, 3)]));
        $processor->execute($state, new ActionRecord(0, Opcode::ActionSubtract, 0, null)); // obj - 3

        $this->assertSame([7.0], $state->stack);
    }

    #[Test]
    public function add2FromSwf()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/add2.swf');
        $processor = new Processor();
        $state = new State();

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $this->assertSame(7, $state->variables['integers']);
        $this->assertSame(3.5, $state->variables['float_and_integer']);
        $this->assertSame('foobar', $state->variables['strings']);
        $this->assertSame('34', $state->variables['numeric_string_and_number']);
        $this->assertSame('43', $state->variables['number_and_numeric_string']);
        $this->assertSame('1.5x', $state->variables['float_and_string']);
        $this->assertSame(2, $state->variables['boolean_and_number']);
        $this->assertNan($state->variables['null_and_number']);
        $this->assertSame('nulla', $state->variables['null_and_string']);
        $this->assertSame('truea', $state->variables['boolean_and_string']);
        $this->assertSame('33', $state->variables['chained']);
        $this->assertSame('[object Object]1', $state->variables['object_and_number']);
        $this->assertSame('1,23', $state->variables['inline_arrays']);
        $this->assertSame('1,2,3', $state->variables['array_and_string']);
    }

    #[Test]
    public function addAndStringAddFromSwf4()
    {
        // SWF 4 compiler push all literals as strings, and use ActionAdd for "+" and ActionStringAdd for "&"
        $swf = new SwfFile(__DIR__.'/../Fixtures/swf4_add.swf');
        $processor = new Processor();
        $state = new State();

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $this->assertSame([
            'add_integers' => 7.0,
            'add_float_and_integer' => 3.5,
            'add_numeric_strings' => 7.5,
            'add_non_numeric_string' => 5.0,
            'string_add_strings' => 'foobar',
            'string_add_integers' => '34',
            'string_add_float' => 'x1.5',
        ], $state->variables);
    }

    #[Test]
    public function subtractFromSwf()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/subtract.swf');
        $processor = new Processor();
        $state = new State();

        foreach ($swf->tags(12) as $tag) {
            $processor->run($tag->actions, $state);
        }

        $this->assertSame(7, $state->variables['integers']);
        $this->assertSame(-7, $state->variables['operand_order']);
        $this->assertSame(2.5, $state->variables['float_and_integer']);
        $this->assertSame(5.5, $state->variables['numeric_strings']);
        $this->assertSame(7.0, $state->variables['numeric_string_and_integer']);
        $this->assertNan($state->variables['non_numeric_string']);
        $this->assertSame(0, $state->variables['boolean_and_integer']);
        $this->assertNan($state->variables['null_and_integer']);
        $this->assertSame(5, $state->variables['chained']);
        $this->assertSame(9.0, $state->variables['concat_then_subtract']);
        $this->assertSame(4.0, $state->variables['array_and_integer']);
        $this->assertNan($state->variables['object_and_integer']);
    }
}
