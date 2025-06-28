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

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\EditTextLayout;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\SwfReader;

final readonly class DefineEditTextTag
{
    public const int TYPE = 37;

    public function __construct(
        public int $characterId,
        public Rectangle $bounds,
        public bool $wordWrap,
        public bool $multiline,
        public bool $password,
        public bool $readOnly,
        public bool $autoSize,
        public bool $noSelect,
        public bool $border,
        public bool $wasStatic,
        public bool $html,
        public bool $useOutlines,
        public ?int $fontId,
        public ?string $fontClass,
        public ?int $fontHeight,
        public ?Color $textColor,
        public ?int $maxLength,
        public ?EditTextLayout $layout,
        public string $variableName,
        public ?string $initialText,
    ) {}

    /**
     * Read a DefineEditText tag
     *
     * @param SwfReader $reader
     *
     * @return self
     */
    public static function read(SwfReader $reader): self
    {
        $characterId = $reader->readUI16();
        $bounds = Rectangle::read($reader);

        $flags = $reader->readUI8();
        $hasText      = ($flags & 0b10000000) === 0b10000000;
        $wordWrap     = ($flags & 0b01000000) === 0b01000000;
        $multiline    = ($flags & 0b00100000) === 0b00100000;
        $password     = ($flags & 0b00010000) === 0b00010000;
        $readOnly     = ($flags & 0b00001000) === 0b00001000;
        $hasTextColor = ($flags & 0b00000100) === 0b00000100;
        $hasMaxLength = ($flags & 0b00000010) === 0b00000010;
        $hasFont      = ($flags & 0b00000001) === 0b00000001;

        $flags = $reader->readUI8();
        $hasFontClass = ($flags & 0b10000000) === 0b10000000;
        $autoSize     = ($flags & 0b01000000) === 0b01000000;
        $hasLayout    = ($flags & 0b00100000) === 0b00100000;
        $noSelect     = ($flags & 0b00010000) === 0b00010000;
        $border       = ($flags & 0b00001000) === 0b00001000;
        $wasStatic    = ($flags & 0b00000100) === 0b00000100;
        $html         = ($flags & 0b00000010) === 0b00000010;
        $useOutlines  = ($flags & 0b00000001) === 0b00000001;

        return new DefineEditTextTag(
            characterId: $characterId,
            bounds: $bounds,
            wordWrap: $wordWrap,
            multiline: $multiline,
            password: $password,
            readOnly: $readOnly,
            autoSize: $autoSize,
            noSelect: $noSelect,
            border: $border,
            wasStatic: $wasStatic,
            html: $html,
            useOutlines: $useOutlines,
            fontId: $hasFont ? $reader->readUI16() : null,
            fontClass: $hasFontClass ? $reader->readNullTerminatedString() : null,
            fontHeight: $hasFont ? $reader->readUI16() : null,
            textColor: $hasTextColor ? Color::readRgba($reader) : null,
            maxLength: $hasMaxLength ? $reader->readUI16() : null,
            layout: $hasLayout ? EditTextLayout::read($reader) : null,
            variableName: $reader->readNullTerminatedString(),
            initialText: $hasText ? $reader->readNullTerminatedString() : null,
        );
    }
}
