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

        $startRecords = $tag->startEdges;
        $endRecords = $this->injectStylesOnEndRecords($startRecords, $tag->endEdges);

        $startPaths = $this->shapeProcessor->processRecords($startRecords, $startFillStyles, $startLineStyles);
        $endPaths = $this->shapeProcessor->processRecords($endRecords, $endFillStyles, $endLineStyles);

        $morphPaths = [];

        foreach ($startPaths as $index => $startPath) {
            $endPath = $endPaths[$index] ?? $startPaths;
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
     * @param list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord> $startRecords
     * @param list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord> $endRecords
     *
     * @return list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord>
     */
    private function injectStylesOnEndRecords(array $startRecords, array $endRecords): array
    {
        $result = [];
        $endRecordsIndex = 0;

        foreach ($startRecords as $startRecord) {
            $endRecord = $endRecords[$endRecordsIndex] ?? $startRecord;

            if (!$startRecord instanceof StyleChangeRecord) {
                $result[] = $endRecord;
                ++$endRecordsIndex;
                continue;
            }

            // Merge style change records
            if ($endRecord instanceof StyleChangeRecord) {
                $result[] = new StyleChangeRecord(
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

            $result[] = $startRecord;
        }

        return $result;
    }
}
