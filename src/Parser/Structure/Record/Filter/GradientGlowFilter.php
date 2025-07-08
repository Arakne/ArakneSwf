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

namespace Arakne\Swf\Parser\Structure\Record\Filter;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\SwfReader;
use Override;

use function assert;
use function count;

final readonly class GradientGlowFilter extends Filter
{
    public const int FILTER_ID = 4;

    public function __construct(
        public int $numColors,
        /** @var list<Color> */
        public array $gradientColors,
        /** @var list<int> */
        public array $gradientRatio,
        public float $blurX,
        public float $blurY,
        public float $angle,
        public float $distance,
        public float $strength,
        public bool $innerShadow,
        public bool $knockout,
        public bool $compositeSource,
        public bool $onTop,
        public int $passes,
    ) {
        assert(count($this->gradientColors) === $this->numColors);
        assert(count($this->gradientRatio) === $this->numColors);
    }

    #[Override]
    protected static function read(SwfReader $reader): static
    {
        $numColors = $reader->readUI8();
        $gradientColors = [];
        $gradientRatio = [];

        for ($i = 0; $i < $numColors; ++$i) {
            $gradientColors[] = Color::readRgba($reader);
        }

        for ($i = 0; $i < $numColors; ++$i) {
            $gradientRatio[] = $reader->readUI8();
        }

        return new GradientGlowFilter(
            numColors: $numColors,
            gradientColors: $gradientColors,
            gradientRatio: $gradientRatio,
            blurX: $reader->readFixed(),
            blurY: $reader->readFixed(),
            angle: $reader->readFixed(),
            distance: $reader->readFixed(),
            strength: $reader->readFixed8(),
            innerShadow: $reader->readBool(),
            knockout: $reader->readBool(),
            compositeSource: $reader->readBool(),
            onTop: $reader->readBool(),
            passes: $reader->readUB(4),
        );
    }
}
