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

use Arakne\Swf\Parser\Structure\Record\FontLayout;
use Arakne\Swf\Parser\Structure\Record\Shape\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\ShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StyleChangeRecord;
use Arakne\Swf\Parser\SwfReader;

use function substr;

final readonly class DefineFont2Or3Tag
{
    public const int TYPE_V2 = 48;
    public const int TYPE_V3 = 75;

    public function __construct(
        public int $version,
        public int $fontId,
        public bool $fontFlagsShiftJIS,
        public bool $fontFlagsSmallText,
        public bool $fontFlagsANSI,
        public bool $fontFlagsWideCodes,
        public bool $fontFlagsItalic,
        public bool $fontFlagsBold,
        public int $languageCode,
        public string $fontName,
        public int $numGlyphs,

        /** @var list<int> */
        public array $offsetTable,

        /** @var list<list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord>> */
        public array $glyphShapeTable,

        /** @var list<int> */
        public array $codeTable,
        public ?FontLayout $layout,
    ) {}

    /**
     * Read a DefineFont2 or DefineFont3 tag from the SWF file
     *
     * @param SwfReader $reader
     * @param int<2, 3> $version The tag version (2 or 3)
     *
     * @return self
     */
    public static function read(SwfReader $reader, int $version): self
    {
        $fontId = $reader->readUI16();

        $flags = $reader->readUI8();
        $fontFlagsHasLayout   = ($flags & 0b10000000) !== 0;
        $fontFlagsShiftJIS    = ($flags & 0b01000000) !== 0;
        $fontFlagsSmallText   = ($flags & 0b00100000) !== 0;
        $fontFlagsANSI        = ($flags & 0b00010000) !== 0;
        $fontFlagsWideOffsets = ($flags & 0b00001000) !== 0;
        $fontFlagsWideCodes   = ($flags & 0b00000100) !== 0 || $version > 2; // Wide codes are always used in version 3
        $fontFlagsItalic      = ($flags & 0b00000010) !== 0;
        $fontFlagsBold        = ($flags & 0b00000001) !== 0;

        $languageCode = $reader->readUI8();
        $fontNameLength = $reader->readUI8();
        $fontName = substr($reader->readBytes($fontNameLength), 0, -1); // Remove trailing NULL
        $numGlyphs = $reader->readUI16();

        $offsetTable = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            $offsetTable[] = $fontFlagsWideOffsets ? $reader->readUI32() : $reader->readUI16();
        }

        // CodeTableOffset: not used by the implementation, so simply skip it
        if ($fontFlagsWideOffsets) {
            $reader->skipBytes(4); // UI32
        } else {
            $reader->skipBytes(2); // UI16
        }

        $glyphShapeTable = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            $glyphShapeTable[] = ShapeRecord::readCollection($reader, 1);
        }

        $codeTable = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            $codeTable[] = $fontFlagsWideCodes ? $reader->readUI16() : $reader->readUI8();
        }

        $layout = $fontFlagsHasLayout ? FontLayout::read($reader, $numGlyphs, $fontFlagsWideCodes) : null;

        return new DefineFont2Or3Tag(
            $version,
            $fontId,
            $fontFlagsShiftJIS,
            $fontFlagsSmallText,
            $fontFlagsANSI,
            $fontFlagsWideCodes,
            $fontFlagsItalic,
            $fontFlagsBold,
            $languageCode,
            $fontName,
            $numGlyphs,
            $offsetTable,
            $glyphShapeTable,
            $codeTable,
            $layout
        );
    }
}
