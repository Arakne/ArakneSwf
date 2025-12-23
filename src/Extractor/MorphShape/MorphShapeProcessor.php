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

use Arakne\Swf\Error\Errors;
use Arakne\Swf\Extractor\Error\ProcessingInvalidDataException;
use Arakne\Swf\Extractor\Image\EmptyImage;
use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Shape\CurvedEdge;
use Arakne\Swf\Extractor\Shape\FillType\Bitmap;
use Arakne\Swf\Extractor\Shape\FillType\LinearGradient;
use Arakne\Swf\Extractor\Shape\FillType\RadialGradient;
use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\PathsBuilder;
use Arakne\Swf\Extractor\Shape\PathStyle;
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Extractor\Shape\StraightEdge;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\Structure\Record\Shape\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\FillStyle;
use Arakne\Swf\Parser\Structure\Record\Shape\LineStyle;
use Arakne\Swf\Parser\Structure\Record\Shape\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StyleChangeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShape2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShapeTag;

use function assert;
use function count;
use function sprintf;
use function var_dump;

/**
 * Process define morph shape action tags to create morph shape objects
 */
final readonly class MorphShapeProcessor
{
    public function __construct(
        private SwfExtractor $extractor,
    ) {}

    public function process(DefineMorphShapeTag|DefineMorphShape2Tag $tag): MorphShape
    {
        $startFillStyles = [];
        $endFillStyles = [];
        $startLineStyles = [];
        $endLineStyles = [];

        foreach ($tag->fillStyles as $morphFillStyle) {
            $startFillStyles[] = new FillStyle(
                type: $morphFillStyle->type,
                color: $morphFillStyle->startColor,
                matrix: $morphFillStyle->startGradientMatrix,
                //gradient: $morphFillStyle->gradient?->startGradient, // @todo implement morph gradient
                bitmapId: $morphFillStyle->bitmapId,
                bitmapMatrix: $morphFillStyle->startBitmapMatrix,
            );
            $endFillStyles[] = new FillStyle(
                type: $morphFillStyle->type,
                color: $morphFillStyle->endColor,
                matrix: $morphFillStyle->endGradientMatrix,
                //gradient: $morphFillStyle->gradient?->startGradient, // @todo implement morph gradient
                bitmapId: $morphFillStyle->bitmapId,
                bitmapMatrix: $morphFillStyle->endBitmapMatrix,
            );
        }

        foreach ($tag->lineStyles as $morphLineStyle) {
            $startLineStyles[] = new LineStyle(
                width: $morphLineStyle->startWidth,
                color: $morphLineStyle->startColor,
                startCapStyle: $morphLineStyle->startCapStyle ?? null,
                joinStyle: $morphLineStyle->joinStyle ?? null,
                hasFillFlag: ($morphLineStyle->fillStyle ?? null) !== null, // @todo gérer proprement
                noHScaleFlag: $morphLineStyle->noHScale ?? null,
                noVScaleFlag: $morphLineStyle->noVScale ?? null,
                pixelHintingFlag: $morphLineStyle->pixelHinting ?? null,
                noClose: $morphLineStyle->noClose ?? null,
                endCapStyle: $morphLineStyle->endCapStyle ?? null,
                miterLimitFactor: $morphLineStyle->miterLimitFactor ?? null,
                fillType: isset($morphLineStyle->fillStyle) ? new FillStyle( // @todo factoriser avec le code plus haut
                    type: $morphLineStyle->fillStyle->type,
                    color: $morphLineStyle->fillStyle->startColor,
                    matrix: $morphLineStyle->fillStyle->startGradientMatrix,
                    //gradient: $morphLineStyle->fillStyle->gradient?->startGradient, // @todo implement morph gradient
                    bitmapId: $morphLineStyle->fillStyle->bitmapId,
                    bitmapMatrix: $morphLineStyle->fillStyle->startBitmapMatrix,
                ) : null,
            );
            $endLineStyles[] = new LineStyle(
                width: $morphLineStyle->startWidth,
                color: $morphLineStyle->endColor,
                startCapStyle: $morphLineStyle->startCapStyle ?? null,
                joinStyle: $morphLineStyle->joinStyle ?? null,
                hasFillFlag: ($morphLineStyle->fillStyle ?? null) !== null, // @todo gérer proprement
                noHScaleFlag: $morphLineStyle->noHScale ?? null,
                noVScaleFlag: $morphLineStyle->noVScale ?? null,
                pixelHintingFlag: $morphLineStyle->pixelHinting ?? null,
                noClose: $morphLineStyle->noClose ?? null,
                endCapStyle: $morphLineStyle->endCapStyle ?? null,
                miterLimitFactor: $morphLineStyle->miterLimitFactor ?? null,
                fillType: isset($morphLineStyle->fillStyle) ? new FillStyle( // @todo factoriser avec le code plus haut
                    type: $morphLineStyle->fillStyle->type,
                    color: $morphLineStyle->fillStyle->endColor,
                    matrix: $morphLineStyle->fillStyle->endGradientMatrix,
                    //gradient: $morphLineStyle->fillStyle->gradient?->startGradient, // @todo implement morph gradient
                    bitmapId: $morphLineStyle->fillStyle->bitmapId,
                    bitmapMatrix: $morphLineStyle->fillStyle->endBitmapMatrix,
                ) : null,
            );
        }

        $endRecords = [];
        $endRecordsIndex = 0;

        foreach ($tag->startEdges as $startRecord) {
            $endRecord = $tag->endEdges[$endRecordsIndex];

            if (!$startRecord instanceof StyleChangeRecord) {
                $endRecords[] = $endRecord;
                ++$endRecordsIndex;
                continue;
            }

            // Merge style change records
            if ($endRecord instanceof StyleChangeRecord) {
                $endRecords[] = new StyleChangeRecord(
                    stateNewStyles: $startRecord->stateNewStyles,
                    stateLineStyle: $startRecord->stateLineStyle,
                    stateFillStyle0: $startRecord->stateFillStyle0,
                    stateFillStyle1: $startRecord->stateFillStyle1,
                    stateMoveTo: $endRecord->stateMoveTo,
                    moveDeltaX: $endRecord->moveDeltaX,
                    moveDeltaY: $endRecord->moveDeltaY,
                    fillStyle0: $startRecord->fillStyle0,
                    fillStyle1: $startRecord->fillStyle1,
                    lineStyle: $startRecord->lineStyle,
                    fillStyles: $endRecord->fillStyles,
                    lineStyles: $endRecord->lineStyles,
                );
                ++$endRecordsIndex;
                continue;
            }

            $endRecords[] = $startRecord;
        }

        $startPaths = $this->processPaths($tag->startEdges, $startFillStyles, $startLineStyles);
        $endPaths = $this->processPaths($endRecords, $endFillStyles, $endLineStyles);

        if (count($startPaths) !== count($endPaths)) {
            if ($this->extractor->errorEnabled(Errors::UNPROCESSABLE_DATA)) {
                throw new ProcessingInvalidDataException('The number of start paths does not match the number of end paths in the morph shape');
            }
        }

        return new MorphShape(
            new Shape(
                $tag->startBounds->width(),
                $tag->startBounds->height(),
                -$tag->startBounds->xmin,
                -$tag->startBounds->ymin,
                $startPaths,
            ),
            new Shape(
                $tag->endBounds->width(),
                $tag->endBounds->height(),
                -$tag->endBounds->xmin,
                -$tag->endBounds->ymin,
                $endPaths,
            ),
        );
    }

    /**
     * @todo mettre en commun avec ShapeProcessor
     *
     * @param list<StyleChangeRecord|StraightEdgeRecord|CurvedEdgeRecord|EndShapeRecord> $shapeRecords
     * @param list<FillStyle> $fillStyles
     * @param list<LineStyle> $lineStyles
     *
     * @return list<Path>
     */
    private function processPaths(array $shapeRecords, array $fillStyles, array $lineStyles): array
    {
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

        foreach ($shapeRecords as $shape) {
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
                        if ($style !== null && $style->width > 0) {
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
            }
        }

        return $builder->export();
    }

    private function createFillType(FillStyle $style): Solid|LinearGradient|RadialGradient|Bitmap
    {
        return match ($style->type) {
            FillStyle::SOLID => $this->createSolidFill($style),
            FillStyle::LINEAR_GRADIENT => $this->createLinearGradientFill($style),
            FillStyle::RADIAL_GRADIENT => $this->createRadialGradientFill($style, $style->gradient),
            FillStyle::FOCAL_GRADIENT => $this->createRadialGradientFill($style, $style->focalGradient),
            FillStyle::REPEATING_BITMAP => $this->createBitmapFill($style, smoothed: true, repeat: true),
            FillStyle::CLIPPED_BITMAP => $this->createBitmapFill($style, smoothed: true, repeat: false),
            FillStyle::NON_SMOOTHED_REPEATING_BITMAP => $this->createBitmapFill($style, smoothed: false, repeat: true),
            FillStyle::NON_SMOOTHED_CLIPPED_BITMAP => $this->createBitmapFill($style, smoothed: false, repeat: false),
            default => $this->extractor->errorEnabled(Errors::UNPROCESSABLE_DATA)
                ? throw new ProcessingInvalidDataException(sprintf('Unknown fill style: %d', $style->type))
                : new Solid(new Color(0, 0, 0, 0))
        };
    }

    private function createSolidFill(FillStyle $style): Solid
    {
        $color = $style->color;
        assert($color !== null);

        return new Solid($color);
    }

    private function createLinearGradientFill(FillStyle $style): LinearGradient
    {
        $matrix = $style->matrix;
        $gradient = $style->gradient;

        assert($matrix !== null && $gradient !== null);

        return new LinearGradient($matrix, $gradient);
    }

    private function createRadialGradientFill(FillStyle $style, ?Gradient $gradient): RadialGradient
    {
        $matrix = $style->matrix;

        assert($matrix !== null);
        assert($gradient !== null);

        return new RadialGradient($matrix, $gradient);
    }

    private function createBitmapFill(FillStyle $style, bool $smoothed, bool $repeat): Bitmap
    {
        $bitmapId = $style->bitmapId;
        $matrix = $style->bitmapMatrix;

        assert($bitmapId !== null && $matrix !== null);

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
}
