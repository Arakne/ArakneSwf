<?php

namespace Arakne\Swf\Avm;

use Arakne\Swf\Avm\Api\ScriptObject;
use Stringable;

use function array_map;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_nan;
use function is_numeric;
use function is_scalar;

/**
 * Utilities for type conversion
 */
final class TypeUtils
{
    /**
     * Implements the ECMAScript "toPrimitive" directive
     *
     * @param mixed $value
     * @return int|string|bool|null|float
     *
     * @see https://262.ecma-international.org/6.0/#sec-toprimitive
     */
    public static function toPrimitive(mixed $value): int|string|bool|null|float
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return implode(',', array_map(self::toString(...), $value));
            } else {
                $value = new ScriptObject($value);
            }
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        // @todo handle valueOf and number hint
        return '[object Object]';
    }

    /**
     * Get the ECMAScript string representation of a value
     */
    public static function toString(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            $value === true => 'true',
            $value === false => 'false',
            is_float($value) && is_nan($value) => 'NaN',
            $value === INF => 'Infinity',
            $value === -INF => '-Infinity',
            is_scalar($value) => (string) $value,
            is_array($value) && array_is_list($value) => implode(',', array_map(self::toString(...), $value)),
            default => (string) self::toPrimitive($value),
        };
    }

    /**
     * Get the numeric representation of a value
     * If the value is not a valid numeric value, NaN will be used.
     */
    public static function toNumeric(mixed $value): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        $value = self::toPrimitive($value);

        return is_numeric($value) ? (float) $value : NAN;
    }
}
