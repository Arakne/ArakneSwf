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

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\SwfReader;

final readonly class LineStyle
{
    public function __construct(
        public int $width,
        public ?Color $color = null,
        public ?int $startCapStyle = null,
        public ?int $joinStyle = null,
        public ?bool $hasFillFlag = null,
        public ?bool $noHScaleFlag = null,
        public ?bool $noVScaleFlag = null,
        public ?bool $pixelHintingFlag = null,
        public ?bool $noClose = null,
        public ?int $endCapStyle = null,
        public ?int $miterLimitFactor = null,
        public ?FillStyle $fillType = null,
    ) {}

    /**
     * Read a collection of LineStyle from the reader
     * The count of elements is determined by the first byte (or 3 bytes for extended).
     *
     * @param SwfReader $reader
     * @param int<1, 4> $version The version of the shape tag
     *
     * @return list<self>
     */
    public static function readCollection(SwfReader $reader, int $version): array
    {
        $lineStyleArray = [];
        $lineStyleCount = $reader->readUI8();

        if ($lineStyleCount === 0xff) {
            $lineStyleCount = $reader->readUI16();
        }

        if ($version < 4) {
            for ($i = 0; $i < $lineStyleCount; $i++) {
                $lineStyleArray[] = new LineStyle(
                    width: $reader->readUI16(),
                    color: $version < 3 ? Color::readRgb($reader) : Color::readRgba($reader),
                );
            }

            return $lineStyleArray;
        }

        for ($i = 0; $i < $lineStyleCount; $i++) {
            $width = $reader->readUI16();

            $flags = $reader->readUI8();
            $startCapStyle = ($flags >> 6) & 0b11; // 2bits
            $joinStyle = ($flags >> 4) & 0b11; // 4bits
            $hasFillFlag = ($flags & 0b1000) !== 0; // 5bits
            $noHScaleFlag = ($flags & 0b100) !== 0; // 6bits
            $noVScaleFlag = ($flags & 0b10) !== 0; // 7 bits
            $pixelHintingFlag = ($flags & 0b1) !== 0; // 8 bits

            $flags = $reader->readUI8();
            // 5bits skipped
            $noClose = ($flags & 0b100) !== 0; // 6bits
            $endCapStyle = $flags & 0b11; // 8bits

            $miterLimitFactor = $joinStyle === 2 ? $reader->readUI16() : null;

            if (!$hasFillFlag) {
                $color = Color::readRgba($reader);
                $fillType = null;
            } else {
                $fillType = FillStyle::read($reader, $version);
                $color = null;
            }

            $lineStyleArray[] = new LineStyle(
                width: $width,
                color: $color,
                startCapStyle: $startCapStyle,
                joinStyle: $joinStyle,
                hasFillFlag: $hasFillFlag,
                noHScaleFlag: $noHScaleFlag,
                noVScaleFlag: $noVScaleFlag,
                pixelHintingFlag: $pixelHintingFlag,
                noClose: $noClose,
                endCapStyle: $endCapStyle,
                miterLimitFactor: $miterLimitFactor,
                fillType: $fillType,
            );
        }

        return $lineStyleArray;
    }
}
