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

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

final readonly class DefineEditTextTag
{
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

        /** @var array<string, mixed>|null */
        public ?array $layout,
        public string $variableName,
        public ?string $initialText,
    ) {}
}
