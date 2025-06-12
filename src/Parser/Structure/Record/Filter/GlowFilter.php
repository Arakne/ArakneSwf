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

namespace Arakne\Swf\Parser\Structure\Record\Filter;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\SwfReader;
use Override;

final readonly class GlowFilter extends Filter
{
    public const int FILTER_ID = 2;

    public function __construct(
        public Color $glowColor,
        public float $blurX,
        public float $blurY,
        public float $strength,
        public bool $innerGlow,
        public bool $knockout,
        public bool $compositeSource,
        public int $passes,
    ) {}

    #[Override]
    protected static function read(SwfReader $reader): static
    {
        return new GlowFilter(
            glowColor: Color::readRgba($reader),
            blurX: $reader->readFixed(),
            blurY: $reader->readFixed(),
            strength: $reader->readFixed8(),
            innerGlow: $reader->readBool(),
            knockout: $reader->readBool(),
            compositeSource: $reader->readBool(),
            passes: $reader->readUB(5),
        );
    }
}
