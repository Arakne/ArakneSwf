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

namespace Arakne\Swf\Extractor\MorphShape;

use Arakne\Swf\Extractor\RatioDrawableInterface;
use Arakne\Swf\Extractor\Shape\FillType\Bitmap;
use Arakne\Swf\Extractor\Shape\FillType\LinearGradient;
use Arakne\Swf\Extractor\Shape\FillType\RadialGradient;
use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\PathStyle;

use function assert;
use function count;

/**
 * Handles the morphing between two paths
 * Both paths must have the same number of edges
 */
final readonly class MorphPath
{
    public function __construct(
        private Path $start,
        private Path $end,
    ) {
        assert(count($this->start->edges) === count($this->end->edges));
    }

    /**
     * Get the interpolated path at the given ratio
     *
     * @param int<0, 65535> $ratio The interpolation ratio. 0 = start path, 65535 = end path
     * @return Path
     */
    public function interpolate(int $ratio): Path
    {
        if ($ratio <= 0) {
            return $this->start;
        }

        if ($ratio >= RatioDrawableInterface::MAX_RATIO) {
            return $this->end;
        }

        $edges = [];

        foreach ($this->start->edges as $index => $startEdge) {
            $endEdge = $this->end->edges[$index];
            $edges[] = $startEdge->interpolate($endEdge, $ratio);
        }

        return new Path(
            $edges,
            $this->interpolateStyle($this->start->style, $this->end->style, $ratio),
        );
    }

    /**
     * @param PathStyle $start
     * @param PathStyle $end
     * @param int<0, 65535> $ratio
     *
     * @return PathStyle
     */
    private function interpolateStyle(PathStyle $start, PathStyle $end, int $ratio): PathStyle
    {
        return new PathStyle(
            fill: $this->interpolateFillStyle($start->fill, $end->fill, $ratio),
            lineColor: $start->lineColor !== null && $end->lineColor !== null ? MorphShape::interpolateColor($start->lineColor, $end->lineColor, $ratio) : null,
            lineFill: $this->interpolateFillStyle($start->lineFill, $end->lineFill, $ratio),
            lineWidth: MorphShape::interpolateInt($start->lineWidth, $end->lineWidth, $ratio),
        );
    }

    /**
     * @param Solid|RadialGradient|LinearGradient|Bitmap|null $start
     * @param Solid|RadialGradient|LinearGradient|Bitmap|null $end
     * @param int<0, 65535> $ratio
     *
     * @return Solid|RadialGradient|LinearGradient|Bitmap|null
     */
    private function interpolateFillStyle(Solid|RadialGradient|LinearGradient|Bitmap|null $start, Solid|RadialGradient|LinearGradient|Bitmap|null $end, int $ratio): Solid|RadialGradient|LinearGradient|Bitmap|null
    {
        if ($start === null || $end === null || !$start instanceof $end) {
            return null;
        }

        if ($start == $end) {
            return $start;
        }

        return $start->interpolate($end, $ratio);
    }
}
