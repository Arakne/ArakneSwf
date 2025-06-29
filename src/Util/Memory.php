<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2025 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Util;

use function ini_get;
use function memory_get_usage;
use function strtoupper;
use function trim;

/**
 * Utility class for checking memory usage.
 */
final class Memory
{
    /**
     * @var non-negative-int|null
     */
    private static ?int $maxMemory = null;

    /**
     * Get the maximum memory usage in bytes.
     *
     * The value will be resolved from the `memory_limit` ini setting.
     * If no limit is set, it will return `PHP_INT_MAX`.
     *
     * @return non-negative-int
     */
    public static function max(): int
    {
        return self::$maxMemory ??= self::parse((string) ini_get('memory_limit'));
    }

    /**
     * Get the current memory usage in bytes.
     *
     * @return non-negative-int
     */
    public static function current(): int
    {
        return memory_get_usage();
    }

    /**
     * Get the current memory usage as a float, representing the ratio of used memory to the maximum allowed memory.
     * The returned value is between 0.0 and 1.0, where 0.0 means no memory is used and 1.0 means the maximum memory is reached.
     *
     * @return float
     */
    public static function usage(): float
    {
        return self::current() / self::max();
    }

    /**
     * Parse a memory string, following the format used in the `memory_limit` ini setting.
     *
     * Handles values with suffixes like 'K', 'M', or 'G' for kilobytes, megabytes, and gigabytes respectively.
     * If no suffix is provided, it assumes the value is in bytes.
     *
     * If the parsed value is invalid, it defaults to `PHP_INT_MAX`.
     *
     * @param string $memory
     * @return non-negative-int
     */
    public static function parse(string $memory): int
    {
        $memory = strtoupper(trim($memory));

        if ($memory === '') {
            return PHP_INT_MAX;
        }

        $multiplier = match ($memory[-1]) {
            'K' => 2 ** 10,
            'M' => 2 ** 20,
            'G' => 2 ** 30,
            default => 1,
        };

        $value = (int) $memory * $multiplier;

        return $value <= 0 ? PHP_INT_MAX : $value;
    }
}
