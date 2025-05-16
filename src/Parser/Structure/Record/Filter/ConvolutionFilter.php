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

use function assert;
use function count;

final readonly class ConvolutionFilter
{
    public function __construct(
        public int $filterId,
        public int $matrixX,
        public int $matrixY,
        public float $divisor,
        public float $bias,
        /**
         * @var list<float>
         */
        public array $matrix,
        public Color $defaultColor,
        public int $reserved,
        public bool $clamp,
        public bool $preserveAlpha,
    ) {
        assert(count($this->matrix) === $this->matrixX * $this->matrixY);
    }
}
