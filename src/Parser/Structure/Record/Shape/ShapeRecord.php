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

namespace Arakne\Swf\Parser\Structure\Record\Shape;

use Arakne\Swf\Parser\SwfReader;

/**
 * Base type for all shape records.
 */
abstract readonly class ShapeRecord
{
    /**
     * Read a collection of shape records from the SWF reader until the end of the shape is reached (i.e. record with no flags set).
     *
     * @param SwfReader $reader
     * @param int<1, 4> $version The shape record version.
     *
     * @return list<CurvedEdgeRecord|EndShapeRecord|StraightEdgeRecord|StyleChangeRecord>
     */
    final public static function readCollection(SwfReader $reader, int $version): array
    {
        $numFillBits = $reader->readUB(4);
        $numLineBits = $reader->readUB(4);
        $shapeRecords = [];

        for (;;) {
            $edgeRecord = $reader->readBool();

            if ($edgeRecord) {
                $straightFlag = $reader->readBool();
                $numBits = $reader->readUB(4);

                if ($straightFlag) {
                    $generalLineFlag = $reader->readBool();
                    $vertLineFlag = !$generalLineFlag && $reader->readBool();

                    $deltaX = $generalLineFlag || !$vertLineFlag ? $reader->readSB($numBits + 2) : 0;
                    $deltaY = $generalLineFlag || $vertLineFlag ? $reader->readSB($numBits + 2) : 0;

                    $shapeRecords[] = new StraightEdgeRecord($generalLineFlag, $vertLineFlag, $deltaX, $deltaY);
                } else {
                    $shapeRecords[] = new CurvedEdgeRecord(
                        $reader->readSB($numBits + 2),
                        $reader->readSB($numBits + 2),
                        $reader->readSB($numBits + 2),
                        $reader->readSB($numBits + 2),
                    );
                }

                continue;
            }

            // Style change record
            $stateNewStyles = $reader->readBool();
            $stateLineStyle = $reader->readBool();
            $stateFillStyle1 = $reader->readBool();
            $stateFillStyle0 = $reader->readBool();
            $stateMoveTo = $reader->readBool();

            // End of shape
            if (!$stateNewStyles && !$stateLineStyle && !$stateFillStyle1 && !$stateFillStyle0 && !$stateMoveTo) {
                $shapeRecords[] = new EndShapeRecord();
                break;
            }

            if ($stateMoveTo) {
                $moveBits = $reader->readUB(5);
                $moveDeltaX = $reader->readSB($moveBits);
                $moveDeltaY = $reader->readSB($moveBits);
            } else {
                $moveDeltaX = 0;
                $moveDeltaY = 0;
            }

            if ($stateFillStyle0) {
                $fillStyle0 = $reader->readUB($numFillBits);
            } else {
                $fillStyle0 = 0;
            }

            if ($stateFillStyle1) {
                $fillStyle1 = $reader->readUB($numFillBits);
            } else {
                $fillStyle1 = 0;
            }

            if ($stateLineStyle) {
                $lineStyle = $reader->readUB($numLineBits);
            } else {
                $lineStyle = 0;
            }

            if ($stateNewStyles && $version >= 2) {
                $reader->alignByte();
                $newFillStyles = FillStyle::readCollection($reader, $version);
                $newLineStyles = LineStyle::readCollection($reader, $version);
                $numFillBits = $reader->readUB(4);
                $numLineBits = $reader->readUB(4);
            } else {
                $newFillStyles = [];
                $newLineStyles = [];
            }

            $shapeRecords[] = new StyleChangeRecord(
                $stateNewStyles,
                $stateLineStyle,
                $stateFillStyle0,
                $stateFillStyle1,
                $stateMoveTo,
                $moveDeltaX,
                $moveDeltaY,
                $fillStyle0,
                $fillStyle1,
                $lineStyle,
                $newFillStyles,
                $newLineStyles,
            );
        }

        $reader->alignByte();

        return $shapeRecords;
    }
}
