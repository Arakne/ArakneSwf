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

use Arakne\Swf\Parser\Structure\Record\Filter\BevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ConvolutionFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\DropShadowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\Filter;
use Arakne\Swf\Parser\Structure\Record\Filter\GlowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientBevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientGlowFilter;
use Arakne\Swf\Parser\SwfReader;

final readonly class ButtonRecord
{
    public function __construct(
        public bool $stateHitTest,
        public bool $stateDown,
        public bool $stateOver,
        public bool $stateUp,
        public int $characterId,
        public int $placeDepth,
        public Matrix $matrix,
        public ?ColorTransform $colorTransform = null,
        /** @var list<DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter>|null */
        public ?array $filters = null,
        public ?int $blendMode = null,
    ) {}

    /**
     * Read a collection of button records from the SWF reader.
     * The end of the collection is marked by a record with a flags value of 0 (end character flag).
     *
     * @param SwfReader $reader
     * @param int $version The version of the define button tag
     *
     * @return list<self> All records until the character end flag is reached.
     */
    public static function readCollection(SwfReader $reader, int $version): array
    {
        $records = [];

        while ($reader->offset < $reader->end) {
            $flags = $reader->readUI8();

            if ($flags === 0) {
                break;
            }

            // 2 bits reserved
            $hasBlendMode = ($flags & 0b00100000) !== 0;
            $hasFilters   = ($flags & 0b00010000) !== 0;
            $stateHitTest = ($flags & 0b00001000) !== 0;
            $stateDown    = ($flags & 0b00000100) !== 0;
            $stateOver    = ($flags & 0b00000010) !== 0;
            $stateUp      = ($flags & 0b00000001) !== 0;

            $records[] = new self(
                stateHitTest: $stateHitTest,
                stateDown: $stateDown,
                stateOver: $stateOver,
                stateUp: $stateUp,
                characterId: $reader->readUI16(),
                placeDepth: $reader->readUI16(),
                matrix: Matrix::read($reader),
                colorTransform: $version >= 2 ? ColorTransform::read($reader, true) : null,
                filters: $version >= 2 && $hasFilters ? Filter::readCollection($reader) : null,
                blendMode: $version >= 2 && $hasBlendMode ? $reader->readUI8() : null,
            );
        }

        return $records;
    }
}
