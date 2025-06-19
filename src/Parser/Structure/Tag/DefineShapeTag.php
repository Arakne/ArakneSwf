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

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Record\Shape\ShapeWithStyle;
use Arakne\Swf\Parser\SwfReader;

final readonly class DefineShapeTag
{
    public const int TYPE_V1 = 2;
    public const int TYPE_V2 = 22;
    public const int TYPE_V3 = 32;

    public function __construct(
        public int $version,
        public int $shapeId,
        public Rectangle $shapeBounds,
        public ShapeWithStyle $shapes,
    ) {}

    /**
     * Read a DefineShapeTag from the SWF reader.
     *
     * @param SwfReader $reader
     * @param int<1, 3> $version The version of the tag (should be 1, 2, or 3)
     *
     * @return self
     */
    public static function read(SwfReader $reader, int $version): self
    {
        return new DefineShapeTag(
            version: $version,
            shapeId: $reader->readUI16(),
            shapeBounds: Rectangle::read($reader),
            shapes: ShapeWithStyle::read($reader, $version),
        );
    }
}
