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

final readonly class GradientRecord
{
    public function __construct(
        /**
         * The "distance" from the start of the gradient box.
         * 0 means the start of the gradient box, 255 means the end of the gradient box.
         */
        public int $ratio,
        public Color $color,
    ) {}

    public function transformColors(ColorTransform $colorTransform): self
    {
        return new self(
            $this->ratio,
            $colorTransform->transform($this->color),
        );
    }
}
