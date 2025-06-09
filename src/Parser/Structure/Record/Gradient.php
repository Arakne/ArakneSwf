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

namespace Arakne\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\SwfReader;
use JsonSerializable;
use Override;

final readonly class Gradient implements JsonSerializable
{
    public const int SPREAD_MODE_PAD = 0;
    public const int SPREAD_MODE_REFLECT = 1;
    public const int SPREAD_MODE_REPEAT = 2;

    public const int INTERPOLATION_MODE_NORMAL = 0;
    public const int INTERPOLATION_MODE_LINEAR = 1;

    public function __construct(
        public int $spreadMode,
        public int $interpolationMode,
        /** @var list<GradientRecord> */
        public array $records,
        public ?float $focalPoint = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function jsonSerialize(): array
    {
        $ret = [
            'spreadMode' => $this->spreadMode,
            'interpolationMode' => $this->interpolationMode,
            'records' => $this->records,
        ];

        if ($this->focalPoint !== null) {
            $ret['focalPoint'] = $this->focalPoint;
        }

        return $ret;
    }

    public function transformColors(ColorTransform $colorTransform): self
    {
        $records = [];

        foreach ($this->records as $record) {
            $records[] = $record->transformColors($colorTransform);
        }

        return new self(
            $this->spreadMode,
            $this->interpolationMode,
            $records,
            $this->focalPoint,
        );
    }

    /**
     * Read a simple gradient.
     *
     * @param SwfReader $reader
     * @param bool $withAlpha Use RGBA colors if true, RGB colors otherwise.
     *
     * @return self
     */
    public static function read(SwfReader $reader, bool $withAlpha): self
    {
        $flags = $reader->readUI8();
        $spreadMode        = ($flags >> 6) & 3; // 2bits
        $interpolationMode = ($flags >> 4) & 3; // 2bits
        $numRecords        = $flags & 15;       // 4bits

        return new Gradient(
            $spreadMode,
            $interpolationMode,
            self::records($reader, $numRecords, $withAlpha)
        );
    }

    /**
     * Read a facal gradient.
     * Focal gradients are always with alpha colors (RGBA).
     *
     * @param SwfReader $reader
     *
     * @return self
     */
    public static function readFocal(SwfReader $reader): self
    {
        $flags = $reader->readUI8();
        $spreadMode        = ($flags >> 6) & 3; // 2bits
        $interpolationMode = ($flags >> 4) & 3; // 2bits
        $numRecords        = $flags & 15;       // 4bits

        return new Gradient(
            $spreadMode,
            $interpolationMode,
            self::records($reader, $numRecords, true),
            focalPoint: $reader->readFixed8(),
        );
    }

    /**
     * @param SwfReader $reader
     * @param non-negative-int $count
     * @param bool $withAlpha
     *
     * @return list<GradientRecord>
     */
    private static function records(SwfReader $reader, int $count, bool $withAlpha): array
    {
        $gradientRecords = [];

        for ($i = 0; $i < $count; ++$i) {
            $gradientRecords[] = new GradientRecord(
                ratio: $reader->readUI8(),
                color: $withAlpha ? Color::readRgba($reader) : Color::readRgb($reader),
            );
        }

        return $gradientRecords;
    }
}
