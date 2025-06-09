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

use Arakne\Swf\Parser\Structure\Record\Shape\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\ShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StyleChangeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\Parser\SwfReader;

/**
 * Shape structure for {@see DefineShapeTag} and {@see DefineShape4Tag}
 */
final readonly class ShapeWithStyle
{
    public function __construct(
        /**
         * @var list<FillStyle>
         */
        public array $fillStyles,

        /**
         * @var list<LineStyle>
         */
        public array $lineStyles,

        /**
         * @var list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord>
         */
        public array $shapeRecords,
    ) {}

    /**
     * Read a shape with style from the given reader
     *
     * @param SwfReader $reader
     * @param int<1, 4> $version The version of the shape tag
     *
     * @return self
     */
    public static function read(SwfReader $reader, int $version): self
    {
        return new self(
            FillStyle::readCollection($reader, $version),
            LineStyle::readCollection($reader, $version),
            ShapeRecord::readCollection($reader, $version),
        );
    }
}
