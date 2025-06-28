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

use Arakne\Swf\Parser\SwfReader;

final readonly class ZoneRecord
{
    public function __construct(
        /**
         * Should have a length of 2
         *
         * @var list<ZoneData>
         */
        public array $data,
        public bool $maskY,
        public bool $maskX,
    ) {}

    /**
     * Read ZoneRecord until reach the end offset.
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end offset byte position.
     *
     * @return list<self>
     */
    public static function readCollection(SwfReader $reader, int $end): array
    {
        $records = [];

        $chunk = $reader->chunk($reader->offset, $end);
        $reader->skipTo($end);

        while ($chunk->offset < $end) {
            $count = $chunk->readUI8(); // Should be 2
            $data = [];

            for ($i = 0; $i < $count; ++$i) {
                $data[] = new ZoneData(
                    alignmentCoordinate: $chunk->readFloat16(),
                    range: $chunk->readFloat16(),
                );
            }

            $flags = $chunk->readUI8();
            // 6 bits reserved
            $maskY = ($flags & 0b00000010) !== 0;
            $maskX = ($flags & 0b00000001) !== 0;

            $records[] = new self($data, $maskY, $maskX);
        }

        return $records;
    }
}
