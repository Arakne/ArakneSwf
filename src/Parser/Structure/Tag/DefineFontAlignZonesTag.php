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

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Error\ParserOutOfBoundException;
use Arakne\Swf\Parser\Structure\Record\ZoneRecord;
use Arakne\Swf\Parser\SwfReader;

final readonly class DefineFontAlignZonesTag
{
    public const int TYPE = 73;

    public function __construct(
        public int $fontId,
        public int $csmTableHint,

        /** @var list<ZoneRecord> */
        public array $zoneTable,
    ) {}

    /**
     * Read a DefineFontAlignZones tag from the SWF reader
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end byte offset of the tag data
     *
     * @return self
     * @throws ParserOutOfBoundException
     */
    public static function read(SwfReader $reader, int $end): self
    {
        $fontId = $reader->readUI16();
        $flags = $reader->readUI8();

        $csmTableHint = ($flags >> 6) & 3; // 2bits CSMTableHint
        // 6bits Reserved

        $zoneTable = ZoneRecord::readCollection($reader, $end);

        return new DefineFontAlignZonesTag(
            fontId: $fontId,
            csmTableHint: $csmTableHint,
            zoneTable: $zoneTable,
        );
    }
}
