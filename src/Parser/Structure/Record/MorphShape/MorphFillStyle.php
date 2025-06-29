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

use Arakne\Swf\Error\Errors;
use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\SwfReader;

final readonly class MorphFillStyle
{
    public const int SOLID = 0x00;
    public const int LINEAR_GRADIENT = 0x10;
    public const int RADIAL_GRADIENT = 0x12;
    public const int FOCAL_RADIAL_GRADIENT = 0x13;
    public const int REPEATING_BITMAP = 0x40;
    public const int CLIPPED_BITMAP = 0x41;
    public const int NON_SMOOTHED_REPEATING_BITMAP = 0x42;
    public const int NON_SMOOTHED_CLIPPED_BITMAP = 0x43;

    public function __construct(
        public int $type,
        public ?Color $startColor = null,
        public ?Color $endColor = null,
        public ?Matrix $startGradientMatrix = null,
        public ?Matrix $endGradientMatrix = null,
        public ?MorphGradient $gradient = null,
        public ?int $bitmapId = null,
        public ?Matrix $startBitmapMatrix = null,
        public ?Matrix $endBitmapMatrix = null,
    ) {}

    /**
     * Read a MorphFillStyle from the given reader
     *
     * @param SwfReader $reader
     * @return self
     */
    public static function read(SwfReader $reader): self
    {
        $type = $reader->readUI8();

        return match ($type) {
            self::SOLID => new self(
                type: $type,
                startColor: Color::readRgba($reader),
                endColor: Color::readRgba($reader),
            ),
            self::LINEAR_GRADIENT, self::RADIAL_GRADIENT => new self(
                type: $type,
                startGradientMatrix: Matrix::read($reader),
                endGradientMatrix: Matrix::read($reader),
                gradient: MorphGradient::read($reader, focal: false),
            ),
            self::FOCAL_RADIAL_GRADIENT => new self(
                type: $type,
                startGradientMatrix: Matrix::read($reader),
                endGradientMatrix: Matrix::read($reader),
                gradient: MorphGradient::read($reader, focal: true),
            ),
            self::REPEATING_BITMAP, self::CLIPPED_BITMAP, self::NON_SMOOTHED_REPEATING_BITMAP, self::NON_SMOOTHED_CLIPPED_BITMAP => new self(
                type: $type,
                bitmapId: $reader->readUI16(),
                startBitmapMatrix: Matrix::read($reader),
                endBitmapMatrix: Matrix::read($reader),
            ),
            default => ($reader->errors & Errors::INVALID_DATA)
                ? throw new ParserInvalidDataException(sprintf('Unknown MorphFillStyle type: %d', $type), $reader->offset)
                : new self($type)
        };
    }

    /**
     * Read multiple MorphFillStyle from the reader
     * The count of elements is determined by the first byte (or 3 bytes for extended).
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

        for ($i = 0; $i < $count; ++$i) {
            $styles[] = self::read($reader);
        }

        return $styles;
    }
}
