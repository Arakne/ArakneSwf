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

use Arakne\Swf\Parser\SwfReader;

final readonly class DefineFont4Tag
{
    public const int TYPE_V4 = 91;

    public function __construct(
        public int $fontId,
        public bool $italic,
        public bool $bold,
        public string $name,
        public ?string $data,
    ) {}

    /**
     * Read a DefineFont4 tag from the SWF reader
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end offset of the tag data
     *
     * @return self
     */
    public static function read(SwfReader $reader, int $end): self
    {
        $fontId = $reader->readUI16();

        $flags = $reader->readUI8();
        // 5 bits reserved
        $fontFlagsHasFontData = ($flags & 0b00000100) !== 0;
        $fontFlagsItalic      = ($flags & 0b00000010) !== 0;
        $fontFlagsBold        = ($flags & 0b00000001) !== 0;

        $fontName = $reader->readNullTerminatedString();
        $fontData = $fontFlagsHasFontData ? $reader->readBytesTo($end) : null;

        return new DefineFont4Tag(
            fontId: $fontId,
            italic: $fontFlagsItalic,
            bold: $fontFlagsBold,
            name: $fontName,
            data: $fontData,
        );
    }
}
