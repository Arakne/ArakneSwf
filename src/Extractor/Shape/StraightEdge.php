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
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\MorphShape\MorphShape;
use Override;

use function assert;

/**
 * Edge type for lines
 */
final readonly class StraightEdge implements EdgeInterface
{
    public function __construct(
        public int $fromX,
        public int $fromY,
        public int $toX,
        public int $toY,
    ) {}

    #[Override]
    public function reverse(): static
    {
        return new self($this->toX, $this->toY, $this->fromX, $this->fromY);
    }

    #[Override]
    public function draw(PathDrawerInterface $drawer): void
    {
        $drawer->line($this->toX, $this->toY);
    }

    /**
     * Transform the straight edge into an equivalent curved edge
     */
    public function toCurvedEdge(): CurvedEdge
    {
        return new CurvedEdge(
            $this->fromX,
            $this->fromY,
            (int)(($this->fromX + $this->toX) / 2),
            (int)(($this->fromY + $this->toY) / 2),
            $this->toX,
            $this->toY,
        );
    }

    #[Override]
    public function interpolate(EdgeInterface $to, int $ratio): EdgeInterface
    {
        if ($to instanceof StraightEdge) {
            return new StraightEdge(
                MorphShape::interpolateInt($this->fromX, $to->fromX, $ratio),
                MorphShape::interpolateInt($this->fromY, $to->fromY, $ratio),
                MorphShape::interpolateInt($this->toX, $to->toX, $ratio),
                MorphShape::interpolateInt($this->toY, $to->toY, $ratio),
            );
        }

        return $this->toCurvedEdge()->interpolate($to, $ratio);
    }
}
