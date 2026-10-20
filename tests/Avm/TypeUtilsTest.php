<?php

namespace Arakne\Tests\Swf\Avm;

use Arakne\Swf\Avm\Api\ScriptArray;
use Arakne\Swf\Avm\Api\ScriptObject;
use Arakne\Swf\Avm\TypeUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use const INF;
use const NAN;

class TypeUtilsTest extends TestCase
{
    #[Test]
    #[TestWith([null])]
    #[TestWith([true])]
    #[TestWith([false])]
    #[TestWith([42])]
    #[TestWith([1.5])]
    #[TestWith(['foo'])]
    public function toPrimitiveShouldKeepPrimitiveValues(mixed $value)
    {
        $this->assertSame($value, TypeUtils::toPrimitive($value));
    }

    public static function provideToPrimitive(): array
    {
        return [
            'empty list' => [[], ''],
            'list' => [[1, 'a', true, 1.5], '1,a,true,1.5'],
            'nested list' => [[1, [2, 3]], '1,2,3'],
            'associative array' => [['a' => 1], '[object Object]'],
            'associative array with toString' => [['toString' => fn () => 'foo'], 'foo'],
            'object' => [new ScriptObject(), '[object Object]'],
            'object with class name' => [new ScriptObject(className: 'Foo'), '[object Foo]'],
            'array' => [new ScriptArray(1, 2), '1,2'],
        ];
    }

    #[Test]
    #[DataProvider('provideToPrimitive')]
    public function toPrimitive(mixed $value, string $expected)
    {
        $this->assertSame($expected, TypeUtils::toPrimitive($value));
    }

    public static function provideToString(): array
    {
        return [
            'null' => [null, 'null'],
            'true' => [true, 'true'],
            'false' => [false, 'false'],
            'integer' => [42, '42'],
            'negative integer' => [-42, '-42'],
            'float' => [1.5, '1.5'],
            'integer float' => [3.0, '3'],
            'NaN' => [NAN, 'NaN'],
            'infinity' => [INF, 'Infinity'],
            'negative infinity' => [-INF, '-Infinity'],
            'string' => ['foo', 'foo'],
            'list' => [[1, 'a', 1.5], '1,a,1.5'],
            'list with booleans' => [[true, false], 'true,false'],
            'associative array' => [['a' => 1], '[object Object]'],
            'object' => [new ScriptObject(), '[object Object]'],
            'array' => [new ScriptArray(1, 2), '1,2'],
        ];
    }

    #[Test]
    #[DataProvider('provideToString')]
    public function toStringValue(mixed $value, string $expected)
    {
        $this->assertSame($expected, TypeUtils::toString($value));
    }

    public static function provideToNumeric(): array
    {
        return [
            'integer' => [42, 42],
            'float' => [1.5, 1.5],
            'infinity' => [INF, INF],
            'true' => [true, 1],
            'false' => [false, 0],
            'integer string' => ['12', 12.0],
            'float string' => ['1.5', 1.5],
            'negative string' => ['-4', -4.0],
            'exponent string' => ['1e3', 1000.0],
            'single element list' => [[5], 5.0],
            'object with numeric toString' => [new ScriptObject(['toString' => fn () => '42']), 42.0],
        ];
    }

    #[Test]
    #[DataProvider('provideToNumeric')]
    public function toNumeric(mixed $value, int|float $expected)
    {
        $this->assertSame($expected, TypeUtils::toNumeric($value));
    }

    public static function provideToNumericNaN(): array
    {
        return [
            'null' => [null],
            'NaN' => [NAN],
            'non numeric string' => ['abc'],
            'partially numeric string' => ['12abc'],
            'list' => [[1, 2]],
            'object' => [new ScriptObject()],
        ];
    }

    #[Test]
    #[DataProvider('provideToNumericNaN')]
    public function toNumericNaN(mixed $value)
    {
        $this->assertNan(TypeUtils::toNumeric($value));
    }
}
