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

namespace Arakne\Swf\Parser\Structure\Record;

final readonly class FillStyle
{
    public const int SOLID = 0x00;
    public const int LINEAR_GRADIENT = 0x10;
    public const int RADIAL_GRADIENT = 0x12;
    public const int FOCAL_GRADIENT = 0x13;
    public const int REPEATING_BITMAP = 0x40;
    public const int CLIPPED_BITMAP = 0x41;
    public const int NON_SMOOTHED_REPEATING_BITMAP = 0x42;
    public const int NON_SMOOTHED_CLIPPED_BITMAP = 0x43;

    public function __construct(
        public int $type,
        public ?Color $color = null,
        public ?Matrix $matrix = null,
        public ?Gradient $gradient = null,
        public ?array $focalGradient = null,
        public ?int $bitmapId = null,
        public ?Matrix $bitmapMatrix = null,
    ) {}
}
