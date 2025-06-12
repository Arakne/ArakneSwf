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

use Arakne\Swf\Parser\SwfReader;
use Override;

final readonly class BlurFilter extends Filter
{
    public const int FILTER_ID = 1;

    public function __construct(
        public float $blurX,
        public float $blurY,
        public int $passes,
    ) {}

    #[Override]
    protected static function read(SwfReader $reader): static
    {
        return new BlurFilter(
            blurX: $reader->readFixed(),
            blurY: $reader->readFixed(),
            passes: ($reader->readUI8() >> 3) & 31, // 5 bits for passes, 3 bits reserved
        );
    }
}
