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

use Arakne\Swf\Extractor\Shape\ShapeProcessor;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\Structure\Record\GradientRecord;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphFillStyle;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphGradient;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphLineStyle;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphLineStyle2;
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

/**
 * Process define morph shape action tags to create morph shape objects
 */
final readonly class MorphShapeProcessor
{
    private ShapeProcessor $shapeProcessor;

    public function __construct(SwfExtractor $extractor)
    {
        $this->shapeProcessor = new ShapeProcessor($extractor);
    }

    /**
     * Process morph tags to create MorphShape object
     *
     * @param DefineMorphShapeTag|DefineMorphShape2Tag $tag
     * @return MorphShape
     */
    public function process(DefineMorphShapeTag|DefineMorphShape2Tag $tag): MorphShape
    {
        $startFillStyles = [];
        $endFillStyles = [];
        $startLineStyles = [];
        $endLineStyles = [];

        foreach ($tag->fillStyles as $morphFillStyle) {
            $startFillStyles[] = $this->morphFillStyleToFillStyle($morphFillStyle, true);
            $endFillStyles[] = $this->morphFillStyleToFillStyle($morphFillStyle, false);
        }

        foreach ($tag->lineStyles as $morphLineStyle) {
            $startLineStyles[] = $this->morphLineStyleToLineStyle($morphLineStyle, true);
            $endLineStyles[] = $this->morphLineStyleToLineStyle($morphLineStyle, false);
        }

        [$startRecords, $endRecords] = $this->injectStylesOnEndRecords($tag->startEdges, $tag->endEdges);

        $startPaths = $this->shapeProcessor->processRecords($startRecords, $startFillStyles, $startLineStyles);
        $endPaths = $this->shapeProcessor->processRecords($endRecords, $endFillStyles, $endLineStyles);

        assert(count($startPaths) === count($endPaths));

        $morphPaths = [];

        foreach ($startPaths as $index => $startPath) {
            $endPath = $endPaths[$index];
            $morphPaths[] = new MorphPath($startPath, $endPath);
        }

        return new MorphShape(
            $tag->startBounds,
            $tag->endBounds,
            $morphPaths,
        );
    }

    /**
     * Convert a MorphFillStyle to a FillStyle for start or end shape
     *
     * @param MorphFillStyle $morphFillStyle
     * @param bool $start True to get the start fill style, false for the end fill style
     *
     * @return FillStyle
     */
    private function morphFillStyleToFillStyle(MorphFillStyle $morphFillStyle, bool $start): FillStyle
    {
        return new FillStyle(
            type: $morphFillStyle->type,
            color: $start ? $morphFillStyle->startColor : $morphFillStyle->endColor,
            matrix: $start ? $morphFillStyle->startGradientMatrix : $morphFillStyle->endGradientMatrix,
            gradient: $this->morphGradientToGradient($morphFillStyle->gradient, $start),
            bitmapId: $morphFillStyle->bitmapId,
            bitmapMatrix: $start ? $morphFillStyle->startBitmapMatrix : $morphFillStyle->endBitmapMatrix,
        );
    }

    /**
     * Convert a MorphLineStyle or MorphLineStyle2 to a LineStyle for start or end shape
     *
     * @param MorphLineStyle|MorphLineStyle2 $style
     * @param bool $start True to get the start line style, false for the end line style
     *
     * @return LineStyle
     */
    private function morphLineStyleToLineStyle(MorphLineStyle|MorphLineStyle2 $style, bool $start): LineStyle
    {
        return new LineStyle(
            width: $start ? $style->startWidth : $style->endWidth,
            color: $start ? $style->startColor : $style->endColor,
            startCapStyle: $style->startCapStyle ?? null,
            joinStyle: $style->joinStyle ?? null,
            hasFillFlag: $style instanceof MorphLineStyle2 ? $style->fillStyle !== null : null,
            noHScaleFlag: $style->noHScale ?? null,
            noVScaleFlag: $style->noVScale ?? null,
            pixelHintingFlag: $style->pixelHinting ?? null,
            noClose: $style->noClose ?? null,
            endCapStyle: $style->endCapStyle ?? null,
            miterLimitFactor: $style->miterLimitFactor ?? null,
            fillType: isset($style->fillStyle) ? $this->morphFillStyleToFillStyle($style->fillStyle, $start) : null,
        );
    }

    private function morphGradientToGradient(?MorphGradient $gradient, bool $start): ?Gradient
    {
        if ($gradient === null) {
            return null;
        }

        $records = [];

        foreach ($gradient->records as $record) {
            $records[] = $start
                ? new GradientRecord($record->startRatio, $record->startColor)
                : new GradientRecord($record->endRatio, $record->endColor)
            ;
        }

        return new Gradient(
            spreadMode: $gradient->spreadMode,
            interpolationMode: $gradient->interpolationMode,
            records: $records,
        );
    }

    /**
     * Inject styles (i.e. from {@see StyleChangeRecord}) from start records into end records
     * Styles are only defined in start records, so we need to copy them to end records to ensure proper processing
     *
     * The result is two lists of records with same length.
     *
     * @param list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord> $startRecords
     * @param list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord> $endRecords
     *
     * @return list<list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord>>
     */
    private function injectStylesOnEndRecords(array $startRecords, array $endRecords): array
    {
        $resultEndRecords = [];
        $resultStartRecords = [];

        $endRecordsIndex = 0;
        $startRecordsIndex = 0;

        $startRecordsCount = count($startRecords);
        $endRecordsCount = count($endRecords);

        // We need to track the position of start records to properly inject moveTo commands
        $startX = 0;
        $startY = 0;

        while ($startRecordsIndex < $startRecordsCount && $endRecordsIndex < $endRecordsCount) {
            $startRecord = $startRecords[$startRecordsIndex];
            $endRecord = $endRecords[$endRecordsIndex];

            // Both are edge records
            if (!$startRecord instanceof StyleChangeRecord && !$endRecord instanceof StyleChangeRecord) {
                $resultStartRecords[] = $startRecord;
                ++$startRecordsIndex;

                $resultEndRecords[] = $endRecord;
                ++$endRecordsIndex;

                if ($startRecord instanceof StraightEdgeRecord) {
                    $startX += $startRecord->deltaX;
                    $startY += $startRecord->deltaY;
                } elseif ($startRecord instanceof CurvedEdgeRecord) {
                    $startX += $startRecord->controlDeltaX + $startRecord->anchorDeltaX;
                    $startY += $startRecord->controlDeltaY + $startRecord->anchorDeltaY;
                }
                continue;
            }

            // Merge style change records
            if ($startRecord instanceof StyleChangeRecord && $endRecord instanceof StyleChangeRecord) {
                $resultStartRecords[] = $startRecord;
                ++$startRecordsIndex;

                $resultEndRecords[] = new StyleChangeRecord(
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
                    fillStyles: $startRecord->fillStyles,
                    lineStyles: $startRecord->lineStyles,
                );
                ++$endRecordsIndex;

                if ($startRecord->stateMoveTo) {
                    $startX = $startRecord->moveDeltaX;
                    $startY = $startRecord->moveDeltaY;
                }
                continue;
            }

            // In this case, only the start record is a style change record
            // So we inject the style without moving the end records cursor
            if ($startRecord instanceof StyleChangeRecord) {
                $resultStartRecords[] = $startRecord;
                ++$startRecordsIndex;

                $resultEndRecords[] = $startRecord;

                if ($startRecord->stateMoveTo) {
                    $startX = $startRecord->moveDeltaX;
                    $startY = $startRecord->moveDeltaY;
                }
                continue;
            }

            assert($endRecord instanceof StyleChangeRecord);

            // End record is a style change record, but not start record
            // This is used when there is a moveTo on the end shape only
            // So inject a "fake" move to style change record in start records
            $resultStartRecords[] = new StyleChangeRecord(
                stateNewStyles: $endRecord->stateNewStyles,
                stateLineStyle: $endRecord->stateLineStyle,
                stateFillStyle0: $endRecord->stateFillStyle0,
                stateFillStyle1: $endRecord->stateFillStyle1,
                stateMoveTo: $endRecord->stateMoveTo,
                moveDeltaX: $startX,
                moveDeltaY: $startY,
                fillStyle0: $endRecord->fillStyle0,
                fillStyle1: $endRecord->fillStyle1,
                lineStyle: $endRecord->lineStyle,
                fillStyles: $endRecord->fillStyles,
                lineStyles: $endRecord->lineStyles,
            );

            $resultEndRecords[] = $endRecord;
            ++$endRecordsIndex;
        }

        $startSize = count($resultStartRecords);
        $endSize = count($resultStartRecords);

        if (!$resultStartRecords[$startSize - 1] instanceof EndShapeRecord) {
            $resultStartRecords[] = new EndShapeRecord();
        }

        if (!$resultEndRecords[$endSize - 1] instanceof EndShapeRecord) {
            $resultEndRecords[] = new EndShapeRecord();
        }

        assert(count($resultStartRecords) === count($resultEndRecords));

        return [$resultStartRecords, $resultEndRecords];
    }
}
