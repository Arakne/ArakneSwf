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

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Parser\Structure\Record\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\StyleChangeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use InvalidArgumentException;

/**
 * Process define shape action tags to create shape objects
 */
final class ShapeProcessor
{
    /**
     * Transform a DefineShapeTag or DefineShape4Tag into a Shape object
     */
    public function process(DefineShapeTag|DefineShape4Tag $tag): Shape
    {
        return new Shape(
            width: $tag->shapeBounds->width(),
            height: $tag->shapeBounds->height(),
            xOffset: -$tag->shapeBounds->xmin,
            yOffset: -$tag->shapeBounds->ymin,
            paths: $this->processPaths($tag),
        );
    }

    /**
     * @return list<Path>
     */
    private function processPaths(DefineShapeTag|DefineShape4Tag $tag): array
    {
        $fillStyles = $tag->shapes->fillStyles;
        $lineStyles = $tag->shapes->lineStyles;

        $x = 0;
        $y = 0;

        /**
         * @var PathStyle|null $fillStyle0
         * @var PathStyle|null $fillStyle1
         * @var PathStyle|null $lineStyle
         */
        $fillStyle0 = null;
        $fillStyle1 = null;
        $lineStyle = null;

        $builder = new PathsBuilder();
        $edges = [];

        foreach ($tag->shapes->shapeRecords as $shape) {
            switch (true) {
                case $shape instanceof StyleChangeRecord:
                    $builder->merge(...$edges);
                    $edges = [];

                    if ($shape->stateNewStyles) {
                        // Reset styles to ensure that we don't use old styles
                        $builder->close();

                        $fillStyles = $shape->fillStyles;
                        $lineStyles = $shape->lineStyles;
                    }

                    if ($shape->stateLineStyle) {
                        $style = $lineStyles[$shape->lineStyle - 1] ?? null;
                        if ($style !== null) {
                            $lineStyle = new PathStyle(
                                lineColor: $style->color,
                                lineWidth: $style->width,
                            );
                        } else {
                            $lineStyle = null;
                        }
                    }

                    if ($shape->stateFillStyle0) {
                        $style = $fillStyles[$shape->fillStyle0 - 1] ?? null;
                        if ($style !== null) {
                            $fillStyle0 = PathStyle::fromFillStyle($style, reverse: true);
                        } else {
                            $fillStyle0 = null;
                        }
                    }

                    if ($shape->stateFillStyle1) {
                        $style = $fillStyles[$shape->fillStyle1 - 1] ?? null;
                        if ($style !== null) {
                            $fillStyle1 = PathStyle::fromFillStyle($style);
                        } else {
                            $fillStyle1 = null;
                        }
                    }

                    $builder->setActiveStyles($fillStyle0, $fillStyle1, $lineStyle);

                    if ($shape->stateMoveTo) {
                        $x = $shape->moveDeltaX;
                        $y = $shape->moveDeltaY;
                    }
                    break;

                case $shape instanceof StraightEdgeRecord:
                    $toX = $x + $shape->deltaX;
                    $toY = $y + $shape->deltaY;

                    $edges[] = new StraightEdge($x, $y, $toX, $toY);

                    $x = $toX;
                    $y = $toY;
                    break;

                case $shape instanceof CurvedEdgeRecord:
                    $fromX = $x;
                    $fromY = $y;
                    $controlX = $x + $shape->controlDeltaX;
                    $controlY = $y + $shape->controlDeltaY;
                    $toX = $x + $shape->controlDeltaX + $shape->anchorDeltaX;
                    $toY = $y + $shape->controlDeltaY + $shape->anchorDeltaY;

                    $edges[] = new CurvedEdge($fromX, $fromY, $controlX, $controlY, $toX, $toY);

                    $x = $toX;
                    $y = $toY;
                    break;

                case $shape instanceof EndShapeRecord:
                    $builder->merge(...$edges);
                    return $builder->export();

                default:
                    throw new InvalidArgumentException('Unknown shape type: '.$shape::class);
            }
        }

        return $builder->export();
    }
}
