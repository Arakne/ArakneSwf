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

use function assert;
use function count;

final readonly class ConvolutionFilter extends Filter
{
    public const int FILTER_ID = 5;

    public function __construct(
        public int $matrixX,
        public int $matrixY,
        public float $divisor,
        public float $bias,
        /**
         * @var list<float>
         */
        public array $matrix,
        public Color $defaultColor,
        public bool $clamp,
        public bool $preserveAlpha,
    ) {
        assert(count($this->matrix) === $this->matrixX * $this->matrixY);
    }

    #[Override]
    protected static function read(SwfReader $reader): static
    {
        $matrixX = $reader->readUI8();
        $matrixY = $reader->readUI8();
        $divisor = $reader->readFloat();
        $bias = $reader->readFloat();
        $matrix = [];

        for ($i = 0; $i < $matrixX * $matrixY; $i++) {
            $matrix[] = $reader->readFloat();
        }

        $defaultColor = Color::readRgba($reader);
        $flags = $reader->readUI8();
        // 6 bits reserved (should be 0)
        $clamp         = ($flags & 0b00000010) !== 0;
        $preserveAlpha = ($flags & 0b00000001) !== 0;

        return new ConvolutionFilter(
            matrixX: $matrixX,
            matrixY: $matrixY,
            divisor: $divisor,
            bias: $bias,
            matrix: $matrix,
            defaultColor: $defaultColor,
            clamp: $clamp,
            preserveAlpha: $preserveAlpha,
        );
    }
}
