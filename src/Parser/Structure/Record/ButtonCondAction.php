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

namespace Arakne\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Error\Errors;
use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Structure\Action\ActionRecord;
use Arakne\Swf\Parser\SwfReader;

use function sprintf;
use function var_dump;

final readonly class ButtonCondAction
{
    public const int KEY_LEFT_ARROW = 1;
    public const int KEY_RIGHT_ARROW = 2;
    public const int KEY_HOME = 3;
    public const int KEY_END = 4;
    public const int KEY_INSERT = 5;
    public const int KEY_DELETE = 6;
    public const int KEY_BACKSPACE = 8;
    public const int KEY_ENTER = 13;
    public const int KEY_UP_ARROW = 14;
    public const int KEY_DOWN_ARROW = 15;
    public const int KEY_PAGE_UP = 16;
    public const int KEY_PAGE_DOWN = 17;
    public const int KEY_TAB = 18;
    public const int KEY_ESCAPE = 19;

    public function __construct(
        public int $size,
        public bool $idleToOverDown,
        public bool $outDownToIdle,
        public bool $outDownToOverDown,
        public bool $overDownToOutDown,
        public bool $overDownToOverUp,
        public bool $overUpToOverDown,
        public bool $overUpToIdle,
        public bool $idleToOverUp,
        /**
         * The key code to trigger the action.
         * For swf 4 and earlier, this value is always 0.
         * For later versions, the keycode can be one of the KEY_* constants, or the ASCII code between 32 and 126.
         */
        public int $keyPress,
        public bool $overDownToIdle,
        /** @var list<ActionRecord> */
        public array $actions = [],
    ) {}

    /**
     * Parse a ButtonCondAction collection from the reader until the end offset.
     *
     * @param non-negative-int $end The end offset of the collection in bytes
     * @return list<self>
     */
    public static function readCollection(SwfReader $reader, int $end): array
    {
        $actions = [];

        do {
            $start = $reader->offset;
            $size = $reader->readUI16();

            // The size must be at least 4 bytes (2 for size, 2 for flags).
            if ($size !== 0 && $size < 4) {
                if ($reader->errors & Errors::INVALID_DATA) {
                    throw new ParserInvalidDataException(sprintf('Invalid ButtonCondAction size: %d', $size), $start);
                }

                // Ignore the record and skip to the end
                $reader->skipTo($end);
                break;
            }

            $flags = $reader->readUI8();
            $idleToOverDown    = ($flags & 0b10000000) !== 0;
            $outDownToIdle     = ($flags & 0b01000000) !== 0;
            $outDownToOverDown = ($flags & 0b00100000) !== 0;
            $overDownToOutDown = ($flags & 0b00010000) !== 0;
            $overDownToOverUp  = ($flags & 0b00001000) !== 0;
            $overUpToOverDown  = ($flags & 0b00000100) !== 0;
            $overUpToIdle      = ($flags & 0b00000010) !== 0;
            $idleToOverUp      = ($flags & 0b00000001) !== 0;

            $flags = $reader->readUI8();
            $keyPress = ($flags >> 1) & 0b01111111; // 7 bits
            $overDownToIdle = ($flags & 0b00000001) !== 0;

            $endOfRecord = $size === 0 ? $end : $start + $size;
            $actionRecords = ActionRecord::readCollection($reader, $endOfRecord);

            $actions[] = new self(
                size: $size,
                idleToOverDown: $idleToOverDown,
                outDownToIdle: $outDownToIdle,
                outDownToOverDown: $outDownToOverDown,
                overDownToOutDown: $overDownToOutDown,
                overDownToOverUp: $overDownToOverUp,
                overUpToOverDown: $overUpToOverDown,
                overUpToIdle: $overUpToIdle,
                idleToOverUp: $idleToOverUp,
                keyPress: $keyPress,
                overDownToIdle: $overDownToIdle,
                actions: $actionRecords,
            );
        } while ($size !== 0 && $reader->offset < $end);

        return $actions;
    }
}
