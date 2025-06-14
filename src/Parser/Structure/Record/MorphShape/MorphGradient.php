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
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\SwfReader;

/**
 * MorphGradient is not well documented in the SWF specification.
 * Flags are not defined, but there are actually used, and seems to follow the same structure as {@see Gradient}
 */
final readonly class MorphGradient
{
    public const int SPREAD_MODE_PAD = 0;
    public const int SPREAD_MODE_REFLECT = 1;
    public const int SPREAD_MODE_REPEAT = 2;

    public const int INTERPOLATION_MODE_NORMAL = 0;
    public const int INTERPOLATION_MODE_LINEAR = 1;

    public function __construct(
        /**
         * Undocumented flags: use two first bits
         */
        public int $spreadMode,

        /**
         * Undocumented flags: use two following bits
         */
        public int $interpolationMode,

        /**
         * @var list<MorphGradientRecord>
         */
        public array $records,

        /**
         * Only used for Focal Radial Gradient
         */
        public ?float $focalPoint = null,
    ) {}

    /**
     * Read a morph gradient from the given reader.
     *
     * @param SwfReader $reader
     * @param bool $focal Whether the focal point is present in the data
     * @return self
     */
    public static function read(SwfReader $reader, bool $focal): self
    {
        $flags = $reader->readUI8();
        $spreadMode        = ($flags >> 6) & 3; // 2bits
        $interpolationMode = ($flags >> 4) & 3; // 2bits
        $numRecords        = $flags & 15;       // 4bits

        return new MorphGradient(
            $spreadMode,
            $interpolationMode,
            self::records($reader, $numRecords),
            focalPoint: $focal ? $reader->readFixed8() : null,
        );
    }

    /**
     * @param SwfReader $reader
     * @param non-negative-int $count
     *
     * @return list<MorphGradientRecord>
     */
    private static function records(SwfReader $reader, int $count): array
    {
        $gradientRecords = [];

        for ($i = 0; $i < $count; ++$i) {
            $gradientRecords[] = new MorphGradientRecord(
                startRatio: $reader->readUI8(),
                startColor: Color::readRgba($reader),
                endRatio: $reader->readUI8(),
                endColor: Color::readRgba($reader),
            );
        }

        return $gradientRecords;
    }
}
