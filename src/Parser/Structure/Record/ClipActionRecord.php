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

use Arakne\Swf\Parser\Structure\Action\ActionRecord;
use Arakne\Swf\Parser\SwfReader;

final readonly class ClipActionRecord
{
    public function __construct(
        public ClipEventFlags $flags,
        public int $size,

        /**
         * The key code from {@see ButtonCondAction} constants.
         * Null if {@see ClipEventFlags::KEY_PRESS} is not set.
         *
         * @var int|null
         */
        public ?int $keyCode,

        /**
         * @var list<ActionRecord>
         */
        public array $actions,
    ) {}

    /**
     * Read a ClipActionRecord collection from the given reader.
     * The end of the collection is marked by a record with all flags set to 0.
     *
     * @param SwfReader $reader
     * @param int $version The SWF version
     *
     * @return list<self>
     */
    public static function readCollection(SwfReader $reader, int $version): array
    {
        $records = [];

        for (;;) { // @todo handle overflow
            $flags = ClipEventFlags::read($reader, $version);

            if ($flags->flags === 0) {
                break;
            }

            $size = $reader->readUI32();
            $actionsEndOffset = $reader->offset + $size;
            $keyCode = $flags->has(ClipEventFlags::KEY_PRESS) ? $reader->readUI8() : null;
            $actions = ActionRecord::readCollection($reader, $actionsEndOffset);

            $records[] = new self(
                flags: $flags,
                size: $size,
                keyCode: $keyCode,
                actions: $actions,
            );
        }

        return $records;
    }
}
