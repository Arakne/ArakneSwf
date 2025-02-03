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

/**
 * Structure representing a rectangle (two points)
 * Coordinates are in twips (1/20th of a pixel), and can be negative
 */
final readonly class Rectangle
{
    public function __construct(
        public int $xmin,
        public int $xmax,
        public int $ymin,
        public int $ymax,
    ) {}

    public function width(): int
    {
        return $this->xmax - $this->xmin;
    }

    public function height(): int
    {
        return $this->ymax - $this->ymin;
    }
}
