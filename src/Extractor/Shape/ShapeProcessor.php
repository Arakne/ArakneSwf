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

use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Shape\FillType\Bitmap;
use Arakne\Swf\Extractor\Shape\FillType\LinearGradient;
use Arakne\Swf\Extractor\Shape\FillType\RadialGradient;
use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Shape\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\FillStyle;
use Arakne\Swf\Parser\Structure\Record\Shape\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StyleChangeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use InvalidArgumentException;

/**
 * Process define shape action tags to create shape objects
 */
final readonly class ShapeProcessor
{
    public function __construct(
        private SwfExtractor $extractor,
    ) {}

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

        /** @var PathStyle|null $fillStyle0 */
        $fillStyle0 = null;
        /** @var PathStyle|null $fillStyle1 */
        $fillStyle1 = null;
        /** @var PathStyle|null $lineStyle */
        $lineStyle = null;

        $builder = new PathsBuilder();
        $edges = [];

        foreach ($tag->shapes->shapeRecords as $shape) {
            switch (true) {
                case $shape instanceof StyleChangeRecord:
                    $builder->merge(...$edges);
                    $edges = [];

                    if ($shape->reset()) {
                        // Start a new drawing context
                        $builder->finalize();
                    }

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
                                lineFill: $style->fillType ? $this->createFillType($style->fillType) : null,
                                lineWidth: $style->width,
                            );
                        } else {
                            $lineStyle = null;
                        }
                    }

                    if ($shape->stateFillStyle0) {
                        $style = $fillStyles[$shape->fillStyle0 - 1] ?? null;
                        if ($style !== null) {
                            $fillStyle0 = new PathStyle(fill: $this->createFillType($style), reverse: true);
                        } else {
                            $fillStyle0 = null;
                        }
                    }

                    if ($shape->stateFillStyle1) {
                        $style = $fillStyles[$shape->fillStyle1 - 1] ?? null;
                        if ($style !== null) {
                            $fillStyle1 = new PathStyle(fill: $this->createFillType($style));
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

    private function createFillType(FillStyle $style): Solid|LinearGradient|RadialGradient|Bitmap
    {
        return match ($style->type) {
            FillStyle::SOLID => new Solid($style->color ?? new Color(0, 0, 0, 0)), // @todo transparent factory method
            FillStyle::LINEAR_GRADIENT => new LinearGradient($style->matrix ?? new Matrix(), $style->gradient ?? new Gradient(0, 0, [])),
            FillStyle::RADIAL_GRADIENT => new RadialGradient($style->matrix ?? new Matrix(), $style->gradient ?? new Gradient(0, 0, [])),
            FillStyle::FOCAL_GRADIENT => new RadialGradient($style->matrix ?? new Matrix(), $style->focalGradient ?? new Gradient(0, 0, [], 0.0)),
            FillStyle::REPEATING_BITMAP => $this->createBitmapFill($style, smoothed: true, repeat: true),
            FillStyle::CLIPPED_BITMAP => $this->createBitmapFill($style, smoothed: true, repeat: false),
            FillStyle::NON_SMOOTHED_REPEATING_BITMAP => $this->createBitmapFill($style, smoothed: false, repeat: true),
            FillStyle::NON_SMOOTHED_CLIPPED_BITMAP => $this->createBitmapFill($style, smoothed: false, repeat: false),
            default => throw new InvalidArgumentException('Unknown fill style: ' . $style->type),
        };
    }

    private function createBitmapFill(FillStyle $style, bool $smoothed, bool $repeat): Bitmap
    {
        // @todo null image instead of exception
        $character = $this->extractor->character($style->bitmapId ?? throw new InvalidArgumentException('Bitmap id not not set'));

        if (!$character instanceof ImageCharacterInterface) {
            throw new InvalidArgumentException('Bitmap id is not a valid image character');
        }

        return new Bitmap(
            $character,
            $style->bitmapMatrix ?? throw new InvalidArgumentException('Bitmap matrix not set'),
            smoothed: $smoothed,
            repeat: $repeat,
        );
    }
}
