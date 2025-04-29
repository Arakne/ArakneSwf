<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Arakne-Swf: derived from SWF.php
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineFont2Or3Tag
{
    public function __construct(
        public int $version,
        public int $fontId,
        public bool $fontFlagsHasLayout,
        public bool $fontFlagsShiftJIS,
        public bool $fontFlagsSmallText,
        public bool $fontFlagsANSI,
        public bool $fontFlagsWideOffsets,
        public bool $fontFlagsWideCodes,
        public bool $fontFlagsItalic,
        public bool $fontFlagsBold,
        public int $languageCode,
        public string $fontName,
        public int $numGlyphs,
        public array $offsetTable,
        public array $glyphShapeTable,
        public array $codeTable,
        public ?array $layout,
    ) {
    }
}
