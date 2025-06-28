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

final readonly class DefineFontInfoTag
{
    public const int TYPE_V1 = 13;
    public const int TYPE_V2 = 62;

    public function __construct(
        public int $version,
        public int $fontId,
        public string $fontName,
        public bool $fontFlagsSmallText,
        public bool $fontFlagsShiftJIS,
        public bool $fontFlagsANSI,
        public bool $fontFlagsItalic,
        public bool $fontFlagsBold,
        public bool $fontFlagsWideCodes,

        /** @var list<int> */
        public array $codeTable,
        public ?int $languageCode = null,
    ) {}

    /**
     * Read a DefineFontInfo or DefineFontInfo2 tag from the reader.
     *
     * @param SwfReader $reader
     * @param int<1, 2> $version The version of the tag, either 1 or 2.
     * @param non-negative-int $end The end byte offset of the tag.
     *
     * @return self
     */
    public static function read(SwfReader $reader, int $version, int $end): self
    {
        $fondId = $reader->readUI16();
        $fontName = $reader->readBytes($reader->readUI8());

        $flags = $reader->readUI8();
        // 2bits reserved
        $smallText = ($flags & 0b00100000) !== 0;
        $shiftJIS  = ($flags & 0b00010000) !== 0;
        $ansi      = ($flags & 0b00001000) !== 0;
        $italic    = ($flags & 0b00000100) !== 0;
        $bold      = ($flags & 0b00000010) !== 0;
        $wideCodes = ($flags & 0b00000001) !== 0 || $version > 1; // Version 2 always has wide codes (i.e. use 16 bits for codes)

        $languageCode = $version > 1 ? $reader->readUI8() : null;
        $codeTable = $wideCodes ? self::readWideCodeTable($reader, $end) : self::readAsciiCodeTable($reader, $end);

        return new self(
            version: $version,
            fontId: $fondId,
            fontName: $fontName,
            fontFlagsSmallText: $smallText,
            fontFlagsShiftJIS: $shiftJIS,
            fontFlagsANSI: $ansi,
            fontFlagsItalic: $italic,
            fontFlagsBold: $bold,
            fontFlagsWideCodes: $wideCodes,
            codeTable: $codeTable,
            languageCode: $languageCode
        );
    }

    /**
     * @param SwfReader $reader
     * @param non-negative-int $end
     * @return list<int>
     */
    private static function readWideCodeTable(SwfReader $reader, int $end): array
    {
        $codeTable = [];

        while ($reader->offset < $end) {
            $codeTable[] = $reader->readUI16();
        }

        return $codeTable;
    }

    /**
     * @param SwfReader $reader
     * @param non-negative-int $end
     * @return list<int>
     */
    private static function readAsciiCodeTable(SwfReader $reader, int $end): array
    {
        $codeTable = [];

        while ($reader->offset < $end) {
            $codeTable[] = $reader->readUI8();
        }

        return $codeTable;
    }
}
