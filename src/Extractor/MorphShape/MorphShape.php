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
use Arakne\Swf\Extractor\Shape\CurvedEdge;
use Arakne\Swf\Extractor\Shape\EdgeInterface;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Extractor\Shape\StraightEdge;

final readonly class MorphShape
{
    public const int MAX_RATIO = RatioDrawableInterface::MAX_RATIO;

    public function __construct(
        public Shape $startShape,
        public Shape $endShape,
    ) {}

    /**
     * @param int<0, 65535> $ratio
     */
    public function interpolate(int $ratio): Shape
    {
        if ($ratio <= 1) {
            return $this->startShape;
        }

        if ($ratio >= self::MAX_RATIO) {
            return $this->endShape;
        }

        $paths = [];

        foreach ($this->startShape->paths as $index => $startPath) {
            $endPath = $this->endShape->paths[$index];
            $paths[] = $this->interpolatePath($startPath, $endPath, $ratio);
        }

        return new Shape(
            $this->interpolateInt($this->startShape->width, $this->endShape->width, $ratio),
            $this->interpolateInt($this->startShape->height, $this->endShape->height, $ratio),
            $this->interpolateInt($this->startShape->xOffset, $this->endShape->xOffset, $ratio),
            $this->interpolateInt($this->startShape->yOffset, $this->endShape->yOffset, $ratio),
            $paths,
        );
    }

    private function interpolateInt(int $start, int $end, int $ratio): int
    {
        // @todo maybe use bitshifting for performance
        return (int) (($start * (self::MAX_RATIO - $ratio) + $end * $ratio) / self::MAX_RATIO);
    }

    private function interpolatePath(Path $start, Path $end, int $ratio): Path
    {
        $edges = [];

        foreach ($start as $index => $startEdge) {
            $endEdge = $end->at($index);
            $edges[] = $this->interpolateEdge($startEdge, $endEdge, $ratio);
        }

        return new Path(
            $edges,
            $start->style, // @todo interpolate style as well
        );
    }

    private function interpolateEdge(EdgeInterface $startEdge, EdgeInterface $endEdge, int $ratio): EdgeInterface
    {
        if ($startEdge instanceof StraightEdge) {
            if ($endEdge instanceof StraightEdge) {
                return new StraightEdge(
                    $this->interpolateInt($startEdge->fromX, $endEdge->fromX, $ratio),
                    $this->interpolateInt($startEdge->fromY, $endEdge->fromY, $ratio),
                    $this->interpolateInt($startEdge->toX, $endEdge->toX, $ratio),
                    $this->interpolateInt($startEdge->toY, $endEdge->toY, $ratio),
                );
            }

            // @todo toCurvedEdge method
            $startEdge = new CurvedEdge(
                $startEdge->fromX,
                $startEdge->fromY,
                (int)(($startEdge->fromX + $startEdge->toX) / 2),
                (int)(($startEdge->fromY + $startEdge->toY) / 2),
                $startEdge->toX,
                $startEdge->toY,
            );
        }

        if (!$endEdge instanceof CurvedEdge) {
            // @todo toCurvedEdge method
            $endEdge = new CurvedEdge(
                $endEdge->fromX,
                $endEdge->fromY,
                (int)(($endEdge->fromX + $endEdge->toX) / 2),
                (int)(($endEdge->fromY + $endEdge->toY) / 2),
                $endEdge->toX,
                $endEdge->toY,
            );
        }

        return new CurvedEdge(
            $this->interpolateInt($startEdge->fromX, $endEdge->fromX, $ratio),
            $this->interpolateInt($startEdge->fromY, $endEdge->fromY, $ratio),
            $this->interpolateInt($startEdge->controlX, $endEdge->controlX, $ratio),
            $this->interpolateInt($startEdge->controlY, $endEdge->controlY, $ratio),
            $this->interpolateInt($startEdge->toX, $endEdge->toX, $ratio),
            $this->interpolateInt($startEdge->toY, $endEdge->toY, $ratio),
        );
    }
}
