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

final readonly class DefineShape4Tag
{
    public const int TYPE_V4 = 83;

    public function __construct(
        public int $shapeId,
        public Rectangle $shapeBounds,
        public Rectangle $edgeBounds,
        public int $reserved,
        public bool $usesFillWindingRule,
        public bool $usesNonScalingStrokes,
        public bool $usesScalingStrokes,
        public ShapeWithStyle $shapes,
    ) {}

    /**
     * Read a DefineShape4Tag from the SWF reader.
     *
     * @param SwfReader $reader
     *
     * @return self
     */
    public static function read(SwfReader $reader): self
    {
        return new DefineShape4Tag(
            shapeId: $reader->readUI16(),
            shapeBounds: Rectangle::read($reader),
            edgeBounds: Rectangle::read($reader),
            reserved: $reader->readUB(5),
            usesFillWindingRule: $reader->readBool(),
            usesNonScalingStrokes: $reader->readBool(),
            usesScalingStrokes: $reader->readBool(),
            shapes: ShapeWithStyle::read($reader, 4),
        );
    }
}
