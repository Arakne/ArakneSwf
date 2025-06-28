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

use Arakne\Swf\Parser\Error\Errors;
use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\SwfReader;
use Exception;

use function sprintf;

final readonly class FillStyle
{
    public const int SOLID = 0x00;
    public const int LINEAR_GRADIENT = 0x10;
    public const int RADIAL_GRADIENT = 0x12;
    public const int FOCAL_GRADIENT = 0x13;
    public const int REPEATING_BITMAP = 0x40;
    public const int CLIPPED_BITMAP = 0x41;
    public const int NON_SMOOTHED_REPEATING_BITMAP = 0x42;
    public const int NON_SMOOTHED_CLIPPED_BITMAP = 0x43;

    public function __construct(
        public int $type,
        public ?Color $color = null,
        public ?Matrix $matrix = null,
        public ?Gradient $gradient = null,
        public ?Gradient $focalGradient = null,
        public ?int $bitmapId = null,
        public ?Matrix $bitmapMatrix = null,
    ) {}

    /**
     * Read a fill style from the reader.
     *
     * @param SwfReader $reader
     * @param int<1, 4> $version The version of the shape tag.
     *
     * @return self
     */
    public static function read(SwfReader $reader, int $version): self
    {
        $type = $reader->readUI8();

        $style = match ($type) {
            FillStyle::SOLID => new FillStyle($type, color: $version < 3 ? Color::readRgb($reader) : Color::readRgba($reader)),
            FillStyle::LINEAR_GRADIENT, FillStyle::RADIAL_GRADIENT => new FillStyle(
                $type,
                matrix: Matrix::read($reader),
                gradient: Gradient::read($reader, $version > 2)
            ),
            FillStyle::FOCAL_GRADIENT => new FillStyle(
                $type,
                matrix: Matrix::read($reader),
                focalGradient: Gradient::readFocal($reader),
            ),
            FillStyle::REPEATING_BITMAP, FillStyle::CLIPPED_BITMAP, FillStyle::NON_SMOOTHED_REPEATING_BITMAP, FillStyle::NON_SMOOTHED_CLIPPED_BITMAP => new FillStyle(
                $type,
                bitmapId: $reader->readUI16(),
                bitmapMatrix: Matrix::read($reader),
            ),
            default => ($reader->errors & Errors::INVALID_DATA)
                ? throw new ParserInvalidDataException(sprintf('Unsupported FillStyle type %d', $type), $reader->offset)
                : new FillStyle($type),
        };

        $reader->alignByte();

        return $style;
    }

    /**
     * Read a collection of fill styles from the reader.
     * The number of fill styles is defined by the first byte (or 3 if extended).
     *
     * @param SwfReader $reader
     * @param int<1, 4> $version The version of the shape tag.
     *
     * @return list<self>
     */
    public static function readCollection(SwfReader $reader, int $version): array
    {
        $fillStyleCount = $reader->readUI8();
        $fillStyleArray = [];

        if ($version >= 2 && $fillStyleCount === 0xff) {
            $fillStyleCount = $reader->readUI16();
        }

        for ($i = 0; $i < $fillStyleCount; $i++) {
            $fillStyleArray[] = self::read($reader, $version);
        }

        return $fillStyleArray;
    }
}
