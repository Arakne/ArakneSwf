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

final readonly class Gradient
{
    public const int SPREAD_MODE_PAD = 0;
    public const int SPREAD_MODE_REFLECT = 1;
    public const int SPREAD_MODE_REPEAT = 2;

    public const int INTERPOLATION_MODE_NORMAL = 0;
    public const int INTERPOLATION_MODE_LINEAR = 1;

    public function __construct(
        public int $spreadMode,
        public int $interpolationMode,
        /** @var list<GradientRecord> */
        public array $records,
    ) {}

    public function transformColors(array $colorTransform): self
    {
        $records = [];

        foreach ($this->records as $record) {
            $records[] = $record->transformColors($colorTransform);
        }

        return new self(
            $this->spreadMode,
            $this->interpolationMode,
            $records,
        );
    }
}
