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
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Extractor\Shape\ShapeProcessor;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\Shape\FillStyle;
use Arakne\Swf\Parser\Structure\Record\Shape\LineStyle;
use Arakne\Swf\Parser\Structure\Record\Shape\StyleChangeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShape2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShapeTag;

use function count;

/**
 * Process define morph shape action tags to create morph shape objects
 */
final readonly class MorphShapeProcessor
{
    private ShapeProcessor $shapeProcessor;

    public function __construct(
        private SwfExtractor $extractor,
    ) {
        $this->shapeProcessor = new ShapeProcessor($extractor);
    }

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

        $startPaths = $this->shapeProcessor->processRecords($tag->startEdges, $startFillStyles, $startLineStyles);
        $endPaths = $this->shapeProcessor->processRecords($endRecords, $endFillStyles, $endLineStyles);

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
}
