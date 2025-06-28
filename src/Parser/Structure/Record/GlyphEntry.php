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

final readonly class GlyphEntry
{
    public function __construct(
        public int $glyphIndex,
        public int $advance,
    ) {}

    /**
     * Read collection of GlyphEntry.
     * The first byte defines the number of entries to read.
     *
     * @param SwfReader $reader
     * @param int<0, 32> $glyphBits Number of bits used to encode glyph index. {@see GlyphEntry::$glyphIndex}.
     * @param int<0, 32> $advanceBits Number of bits used to encode advance. {@see GlyphEntry::$advance}.
     *
     * @return list<self>
     */
    public static function readCollection(SwfReader $reader, int $glyphBits, int $advanceBits): array
    {
        $count = $reader->readUI8();
        $entries = [];

        for ($i = 0; $i < $count; $i++) {
            $glyphIndex = $reader->readUB($glyphBits);
            $advance = $reader->readSB($advanceBits);

            $entries[] = new self(
                glyphIndex: $glyphIndex,
                advance: $advance,
            );
        }

        return $entries;
    }
}
