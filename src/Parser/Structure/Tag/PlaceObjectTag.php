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

use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\SwfReader;

final readonly class PlaceObjectTag
{
    public const int TYPE = 4;

    public function __construct(
        public int $characterId,
        public int $depth,
        public Matrix $matrix,
        public ?ColorTransform $colorTransform,
    ) {}

    /**
     * Read a PlaceObjectTag from the SWF reader.
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end byte offset for this tag
     *
     * @return self
     */
    public static function read(SwfReader $reader, int $end): self
    {
        return new PlaceObjectTag(
            characterId: $reader->readUI16(),
            depth: $reader->readUI16(),
            matrix: Matrix::read($reader),
            colorTransform: $reader->offset < $end ? ColorTransform::read($reader, false) : null,
        );
    }
}
