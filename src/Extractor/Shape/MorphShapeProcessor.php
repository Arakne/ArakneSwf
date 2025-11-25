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

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Error\Errors;
use Arakne\Swf\Extractor\Error\ProcessingInvalidDataException;
use Arakne\Swf\Extractor\Image\EmptyImage;
use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Shape\FillType\Bitmap;
use Arakne\Swf\Extractor\Shape\FillType\LinearGradient;
use Arakne\Swf\Extractor\Shape\FillType\RadialGradient;
use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\Structure\Record\GradientRecord;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphFillStyle;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphGradient;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphLineStyle;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphLineStyle2;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Record\Shape\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StyleChangeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShape2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShapeTag;

use function assert;
use function count;
use function sprintf;

/**
 * Process DefineMorphShape tags to create interpolated Shape objects at a given ratio.
 *
 * MorphShapes define a start state and an end state, and this processor creates
 * the intermediate shape at any point between them (ratio 0.0 = start, ratio 1.0 = end).
 */
final readonly class MorphShapeProcessor
{
    public function __construct(
        private SwfExtractor $extractor,
    ) {}

    /**
     * Transform a DefineMorphShapeTag or DefineMorphShape2Tag into a Shape object at the given ratio.
     *
     * @param DefineMorphShapeTag|DefineMorphShape2Tag $tag The morph shape tag to process
     * @param float $ratio The interpolation ratio between 0.0 (start) and 1.0 (end)
     *
     * @return Shape The interpolated shape
     */
    public function process(DefineMorphShapeTag|DefineMorphShape2Tag $tag, float $ratio): Shape
    {
        $ratio = max(0.0, min(1.0, $ratio));

        $bounds = $this->interpolateBounds($tag->startBounds, $tag->endBounds, $ratio);

        return new Shape(
            width: $bounds->width(),
            height: $bounds->height(),
            xOffset: -$bounds->xmin,
            yOffset: -$bounds->ymin,
            paths: $this->processPaths($tag, $ratio),
        );
    }

    /**
     * @return list<Path>
     */
    private function processPaths(DefineMorphShapeTag|DefineMorphShape2Tag $tag, float $ratio): array
    {
        $fillStyles = $tag->fillStyles;
        $lineStyles = $tag->lineStyles;

        // Pair up start and end edges
        $startEdges = $this->flattenEdges($tag->startEdges);
        $endEdges = $this->flattenEdges($tag->endEdges);

        $edgeCount = count($startEdges);

        // Current position for start and end shapes
        $startX = 0;
        $startY = 0;
        $endX = 0;
        $endY = 0;

        $endEdgeIndex = 0;

        /** @var PathStyle|null $fillStyle0 */
        $fillStyle0 = null;
        /** @var PathStyle|null $fillStyle1 */
        $fillStyle1 = null;
        /** @var PathStyle|null $lineStyle */
        $lineStyle = null;

        $builder = new PathsBuilder();
        $edges = [];

        for ($i = 0; $i < $edgeCount; $i++) {
            $startShape = $startEdges[$i];
            $endShape = $endEdges[$endEdgeIndex] ?? null;

            if ($startShape instanceof StyleChangeRecord) {
                $builder->merge(...$edges);
                $edges = [];

                if ($startShape->reset()) {
                    $builder->finalize();
                }

                if ($startShape->stateLineStyle) {
                    $style = $lineStyles[$startShape->lineStyle - 1] ?? null;
                    $lineStyle = $style !== null ? $this->createInterpolatedLineStyle($style, $ratio) : null;
                }

                if ($startShape->stateFillStyle0) {
                    $style = $fillStyles[$startShape->fillStyle0 - 1] ?? null;
                    $fillStyle0 = $style !== null
                        ? new PathStyle(fill: $this->createInterpolatedFillType($style, $ratio), reverse: true)
                        : null;
                }

                if ($startShape->stateFillStyle1) {
                    $style = $fillStyles[$startShape->fillStyle1 - 1] ?? null;
                    $fillStyle1 = $style !== null
                        ? new PathStyle(fill: $this->createInterpolatedFillType($style, $ratio))
                        : null;
                }

                $builder->setActiveStyles($fillStyle0, $fillStyle1, $lineStyle);

                if ($startShape->stateMoveTo) {
                    $startX = $startShape->moveDeltaX;
                    $startY = $startShape->moveDeltaY;

                    // Find corresponding moveTo in end edges
                    if ($endShape instanceof StyleChangeRecord && $endShape->stateMoveTo) {
                        $endX = $endShape->moveDeltaX;
                        $endY = $endShape->moveDeltaY;
                        $endEdgeIndex++;
                    }
                }

                continue;
            }

            if ($startShape instanceof EndShapeRecord) {
                $builder->merge(...$edges);
                return $builder->export();
            }

            // Sync end edge position - skip style change records in end edges
            while ($endShape instanceof StyleChangeRecord) {
                if ($endShape->stateMoveTo) {
                    $endX = $endShape->moveDeltaX;
                    $endY = $endShape->moveDeltaY;
                }
                $endEdgeIndex++;
                $endShape = $endEdges[$endEdgeIndex] ?? null;
            }

            // Process edge records
            if ($startShape instanceof StraightEdgeRecord) {
                $startToX = $startX + $startShape->deltaX;
                $startToY = $startY + $startShape->deltaY;

                $endToX = $endX;
                $endToY = $endY;

                if ($endShape instanceof StraightEdgeRecord) {
                    $endToX = $endX + $endShape->deltaX;
                    $endToY = $endY + $endShape->deltaY;
                    $endEdgeIndex++;
                } elseif ($endShape instanceof CurvedEdgeRecord) {
                    // End shape has curve where start has straight line
                    // Interpolate straight line as degenerate curve
                    $endControlX = $endX + $endShape->controlDeltaX;
                    $endControlY = $endY + $endShape->controlDeltaY;
                    $endToX = $endControlX + $endShape->anchorDeltaX;
                    $endToY = $endControlY + $endShape->anchorDeltaY;

                    // Create interpolated curve (with start control = midpoint for straight line)
                    $startMidX = ($startX + $startToX) / 2;
                    $startMidY = ($startY + $startToY) / 2;

                    $edges[] = new CurvedEdge(
                        fromX: $this->lerp($startX, $endX, $ratio),
                        fromY: $this->lerp($startY, $endY, $ratio),
                        controlX: $this->lerp($startMidX, $endControlX, $ratio),
                        controlY: $this->lerp($startMidY, $endControlY, $ratio),
                        toX: $this->lerp($startToX, $endToX, $ratio),
                        toY: $this->lerp($startToY, $endToY, $ratio),
                    );

                    $startX = $startToX;
                    $startY = $startToY;
                    $endX = $endToX;
                    $endY = $endToY;
                    $endEdgeIndex++;
                    continue;
                }

                $edges[] = new StraightEdge(
                    fromX: $this->lerp($startX, $endX, $ratio),
                    fromY: $this->lerp($startY, $endY, $ratio),
                    toX: $this->lerp($startToX, $endToX, $ratio),
                    toY: $this->lerp($startToY, $endToY, $ratio),
                );

                $startX = $startToX;
                $startY = $startToY;
                $endX = $endToX;
                $endY = $endToY;
            } elseif ($startShape instanceof CurvedEdgeRecord) {
                $startControlX = $startX + $startShape->controlDeltaX;
                $startControlY = $startY + $startShape->controlDeltaY;
                $startToX = $startControlX + $startShape->anchorDeltaX;
                $startToY = $startControlY + $startShape->anchorDeltaY;

                $endControlX = $endX;
                $endControlY = $endY;
                $endToX = $endX;
                $endToY = $endY;

                if ($endShape instanceof CurvedEdgeRecord) {
                    $endControlX = $endX + $endShape->controlDeltaX;
                    $endControlY = $endY + $endShape->controlDeltaY;
                    $endToX = $endControlX + $endShape->anchorDeltaX;
                    $endToY = $endControlY + $endShape->anchorDeltaY;
                    $endEdgeIndex++;
                } elseif ($endShape instanceof StraightEdgeRecord) {
                    // End shape has straight line where start has curve
                    // Interpolate with end control = midpoint
                    $endToX = $endX + $endShape->deltaX;
                    $endToY = $endY + $endShape->deltaY;
                    $endControlX = ($endX + $endToX) / 2;
                    $endControlY = ($endY + $endToY) / 2;
                    $endEdgeIndex++;
                }

                $edges[] = new CurvedEdge(
                    fromX: $this->lerp($startX, $endX, $ratio),
                    fromY: $this->lerp($startY, $endY, $ratio),
                    controlX: $this->lerp($startControlX, $endControlX, $ratio),
                    controlY: $this->lerp($startControlY, $endControlY, $ratio),
                    toX: $this->lerp($startToX, $endToX, $ratio),
                    toY: $this->lerp($startToY, $endToY, $ratio),
                );

                $startX = $startToX;
                $startY = $startToY;
                $endX = $endToX;
                $endY = $endToY;
            }
        }

        $builder->merge(...$edges);
        return $builder->export();
    }

    /**
     * Flatten shape records, filtering out non-edge records for easier pairing.
     *
     * @param list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord> $records
     * @return list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord>
     */
    private function flattenEdges(array $records): array
    {
        return $records;
    }

    /**
     * Linear interpolation between two integer values.
     */
    private function lerp(int|float $start, int|float $end, float $ratio): int
    {
        return (int) round($start + ($end - $start) * $ratio);
    }

    /**
     * Linear interpolation for float values.
     */
    private function lerpFloat(float $start, float $end, float $ratio): float
    {
        return $start + ($end - $start) * $ratio;
    }

    /**
     * Interpolate bounds between start and end.
     */
    private function interpolateBounds(Rectangle $start, Rectangle $end, float $ratio): Rectangle
    {
        return new Rectangle(
            xmin: $this->lerp($start->xmin, $end->xmin, $ratio),
            xmax: $this->lerp($start->xmax, $end->xmax, $ratio),
            ymin: $this->lerp($start->ymin, $end->ymin, $ratio),
            ymax: $this->lerp($start->ymax, $end->ymax, $ratio),
        );
    }

    /**
     * Interpolate a color between start and end.
     */
    private function interpolateColor(Color $start, Color $end, float $ratio): Color
    {
        return new Color(
            red: $this->lerp($start->red, $end->red, $ratio),
            green: $this->lerp($start->green, $end->green, $ratio),
            blue: $this->lerp($start->blue, $end->blue, $ratio),
            alpha: $start->alpha !== null || $end->alpha !== null
                ? $this->lerp($start->alpha ?? 255, $end->alpha ?? 255, $ratio)
                : null,
        );
    }

    /**
     * Interpolate a matrix between start and end.
     */
    private function interpolateMatrix(Matrix $start, Matrix $end, float $ratio): Matrix
    {
        return new Matrix(
            scaleX: $this->lerpFloat($start->scaleX, $end->scaleX, $ratio),
            scaleY: $this->lerpFloat($start->scaleY, $end->scaleY, $ratio),
            rotateSkew0: $this->lerpFloat($start->rotateSkew0, $end->rotateSkew0, $ratio),
            rotateSkew1: $this->lerpFloat($start->rotateSkew1, $end->rotateSkew1, $ratio),
            translateX: $this->lerp($start->translateX, $end->translateX, $ratio),
            translateY: $this->lerp($start->translateY, $end->translateY, $ratio),
        );
    }

    /**
     * Create interpolated line style from a MorphLineStyle.
     */
    private function createInterpolatedLineStyle(MorphLineStyle|MorphLineStyle2 $style, float $ratio): PathStyle
    {
        $width = $this->lerp($style->startWidth, $style->endWidth, $ratio);

        if ($style instanceof MorphLineStyle) {
            return new PathStyle(
                lineColor: $this->interpolateColor($style->startColor, $style->endColor, $ratio),
                lineWidth: $width,
            );
        }

        // MorphLineStyle2
        if ($style->fillStyle !== null) {
            return new PathStyle(
                lineFill: $this->createInterpolatedFillType($style->fillStyle, $ratio),
                lineWidth: $width,
            );
        }

        $startColor = $style->startColor ?? new Color(0, 0, 0, 255);
        $endColor = $style->endColor ?? new Color(0, 0, 0, 255);

        return new PathStyle(
            lineColor: $this->interpolateColor($startColor, $endColor, $ratio),
            lineWidth: $width,
        );
    }

    /**
     * Create interpolated fill type from a MorphFillStyle.
     */
    private function createInterpolatedFillType(MorphFillStyle $style, float $ratio): Solid|LinearGradient|RadialGradient|Bitmap
    {
        return match ($style->type) {
            MorphFillStyle::SOLID => $this->createInterpolatedSolidFill($style, $ratio),
            MorphFillStyle::LINEAR_GRADIENT => $this->createInterpolatedLinearGradientFill($style, $ratio),
            MorphFillStyle::RADIAL_GRADIENT,
            MorphFillStyle::FOCAL_RADIAL_GRADIENT => $this->createInterpolatedRadialGradientFill($style, $ratio),
            MorphFillStyle::REPEATING_BITMAP => $this->createInterpolatedBitmapFill($style, $ratio, smoothed: true, repeat: true),
            MorphFillStyle::CLIPPED_BITMAP => $this->createInterpolatedBitmapFill($style, $ratio, smoothed: true, repeat: false),
            MorphFillStyle::NON_SMOOTHED_REPEATING_BITMAP => $this->createInterpolatedBitmapFill($style, $ratio, smoothed: false, repeat: true),
            MorphFillStyle::NON_SMOOTHED_CLIPPED_BITMAP => $this->createInterpolatedBitmapFill($style, $ratio, smoothed: false, repeat: false),
            default => $this->extractor->errorEnabled(Errors::UNPROCESSABLE_DATA)
                ? throw new ProcessingInvalidDataException(sprintf('Unknown morph fill style: %d', $style->type))
                : new Solid(new Color(0, 0, 0, 0))
        };
    }

    private function createInterpolatedSolidFill(MorphFillStyle $style, float $ratio): Solid
    {
        $startColor = $style->startColor;
        $endColor = $style->endColor;
        assert($startColor !== null && $endColor !== null);

        return new Solid($this->interpolateColor($startColor, $endColor, $ratio));
    }

    private function createInterpolatedLinearGradientFill(MorphFillStyle $style, float $ratio): LinearGradient
    {
        $startMatrix = $style->startGradientMatrix;
        $endMatrix = $style->endGradientMatrix;
        $morphGradient = $style->gradient;

        assert($startMatrix !== null && $endMatrix !== null && $morphGradient !== null);

        $matrix = $this->interpolateMatrix($startMatrix, $endMatrix, $ratio);
        $gradient = $this->interpolateGradient($morphGradient, $ratio);

        return new LinearGradient($matrix, $gradient);
    }

    private function createInterpolatedRadialGradientFill(MorphFillStyle $style, float $ratio): RadialGradient
    {
        $startMatrix = $style->startGradientMatrix;
        $endMatrix = $style->endGradientMatrix;
        $morphGradient = $style->gradient;

        assert($startMatrix !== null && $endMatrix !== null && $morphGradient !== null);

        $matrix = $this->interpolateMatrix($startMatrix, $endMatrix, $ratio);
        $gradient = $this->interpolateGradient($morphGradient, $ratio);

        return new RadialGradient($matrix, $gradient);
    }

    private function createInterpolatedBitmapFill(MorphFillStyle $style, float $ratio, bool $smoothed, bool $repeat): Bitmap
    {
        $bitmapId = $style->bitmapId;
        $startMatrix = $style->startBitmapMatrix;
        $endMatrix = $style->endBitmapMatrix;

        assert($bitmapId !== null && $startMatrix !== null && $endMatrix !== null);

        $matrix = $this->interpolateMatrix($startMatrix, $endMatrix, $ratio);
        $character = $this->extractor->character($bitmapId);

        if (!$character instanceof ImageCharacterInterface) {
            if ($this->extractor->errorEnabled(Errors::UNPROCESSABLE_DATA)) {
                throw new ProcessingInvalidDataException(sprintf('The character %d is not a valid image character', $bitmapId));
            }

            $character = new EmptyImage($bitmapId);
        }

        return new Bitmap(
            $character,
            $matrix,
            smoothed: $smoothed,
            repeat: $repeat,
        );
    }

    /**
     * Interpolate a MorphGradient to a regular Gradient at the given ratio.
     */
    private function interpolateGradient(MorphGradient $morphGradient, float $ratio): Gradient
    {
        $records = [];

        foreach ($morphGradient->records as $morphRecord) {
            $records[] = new GradientRecord(
                ratio: $this->lerp($morphRecord->startRatio, $morphRecord->endRatio, $ratio),
                color: $this->interpolateColor($morphRecord->startColor, $morphRecord->endColor, $ratio),
            );
        }

        return new Gradient(
            spreadMode: $morphGradient->spreadMode,
            interpolationMode: $morphGradient->interpolationMode,
            records: $records,
            focalPoint: $morphGradient->focalPoint,
        );
    }
}
