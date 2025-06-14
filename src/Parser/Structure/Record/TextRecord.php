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

final readonly class TextRecord
{
    public function __construct(
        /**
         * Should be always 1
         */
        public int $type,
        public ?int $fontId,
        public ?Color $color,
        public ?int $xOffset,
        public ?int $yOffset,

        /**
         * The text height in twips (1/20 of a pixel).
         * Defined only if {@see TextRecord::$fontId} is not null.
         *
         * @var int|null
         */
        public ?int $height,

        /**
         * @var list<GlyphEntry>
         */
        public array $glyphs = [],
    ) {}

    /**
     * Read a TextRecord collection.
     * Records are read until enmpty flag is found.
     *
     * @param SwfReader $reader
     * @param non-negative-int $glyphBits Number of bits used to encode glyph index. {@see GlyphEntry::$glyphIndex}.
     * @param non-negative-int $advanceBits Number of bits used to encode advance. {@see GlyphEntry::$advance}.
     * @param bool $withAlpha Text color is with alpha channel (for DefineText2)
     *
     * @return list<self>
     */
    public static function readCollection(SwfReader $reader, int $glyphBits, int $advanceBits, bool $withAlpha): array
    {
        $records = [];

        for (;;) { // @todo handle overflow
            $flags = $reader->readUI8();

            if ($flags === 0) {
                break;
            }

            $type = $flags >> 7;
            // 3 bits reserved
            $hasFont = ($flags & 0b1000) !== 0;
            $hasColor = ($flags & 0b0100) !== 0;
            $hasYOffset = ($flags & 0b0010) !== 0;
            $hasXOffset = ($flags & 0b0001) !== 0;

            $records[] = new self(
                type: $type,
                fontId: $hasFont ? $reader->readUI16() : null,
                color: $hasColor ? ($withAlpha ? Color::readRgba($reader) : Color::readRgb($reader)) : null,
                xOffset: $hasXOffset ? $reader->readSI16() : null,
                yOffset: $hasYOffset ? $reader->readSI16() : null,
                height: $hasFont ? $reader->readUI16() : null,
                glyphs: GlyphEntry::readCollection($reader, $glyphBits, $advanceBits),
            );

            $reader->alignByte();
        }

        return $records;
    }
}
