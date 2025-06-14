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

namespace Arakne\Swf\Parser\Structure\Record\MorphShape;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\SwfReader;

final readonly class MorphLineStyle2
{
    public const int CAP_ROUND = 0;
    public const int CAP_NONE = 1;
    public const int CAP_SQUARE = 2;
    public const int JOIN_ROUND = 0;
    public const int JOIN_BEVEL = 1;
    public const int JOIN_MITER = 2;

    public function __construct(
        public int $startWidth,
        public int $endWidth,
        public int $startCapStyle,
        public int $joinStyle,
        public bool $noHScale,
        public bool $noVScale,
        public bool $pixelHinting,
        public bool $noClose,
        public int $endCapStyle,
        /**
         * Only used if joinStyle is JOIN_MITER
         * The value is a fixed 8.8 number
         */
        public ?float $miterLimitFactor,
        public ?Color $startColor,
        public ?Color $endColor,
        public ?MorphFillStyle $fillStyle,
    ) {}

    /**
     * Read a MorphLineStyle2 collection.
     * The collection size is determined by the first byte read (or 3 if extended).
     *
     * @param SwfReader $reader
     * @return list<self>
     */
    public static function readCollection(SwfReader $reader): array
    {
        $count = $reader->readUI8();
        $styles = [];

        if ($count === 0xff) {
            $count = $reader->readUI16();
        }

        for ($i = 0; $i < $count; $i++) {
            $startWidth = $reader->readUI16();
            $endWidth = $reader->readUI16();

            $flags = $reader->readUI8();
            $startCapStyle = ($flags >> 6) & 3; // 2 bits
            $joinStyle = ($flags >> 4) & 3; // 2 bits
            $hasFill      = ($flags & 0b00001000) !== 0;
            $noHScale     = ($flags & 0b00000100) !== 0;
            $noVScale     = ($flags & 0b00000010) !== 0;
            $pixelHinting = ($flags & 0b00000001) !== 0;

            $flags = $reader->readUI8();
            // 5 bits reserved (should be 0)
            $noClose     = ($flags & 0b00000100) !== 0;
            $endCapStyle = ($flags & 0b00000011);

            $styles[] = new self(
                startWidth: $startWidth,
                endWidth: $endWidth,
                startCapStyle: $startCapStyle,
                joinStyle: $joinStyle,
                noHScale: $noHScale,
                noVScale: $noVScale,
                pixelHinting: $pixelHinting,
                noClose: $noClose,
                endCapStyle: $endCapStyle,
                miterLimitFactor: ($joinStyle === self::JOIN_MITER) ? $reader->readFixed8() : null,
                startColor: !$hasFill ? Color::readRgba($reader) : null,
                endColor: !$hasFill ? Color::readRgba($reader) : null,
                fillStyle: $hasFill ? MorphFillStyle::read($reader) : null,
            );
        }

        return $styles;
    }
}
