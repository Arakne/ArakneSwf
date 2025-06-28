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
use Arakne\Swf\Parser\SwfReader;

final readonly class DefineButtonCxformTag
{
    public const int TYPE = 23;

    public function __construct(
        public int $buttonId,
        public ColorTransform $colorTransform,
    ) {}

    /**
     * Read a DefineButtonCxform tag
     *
     * @param SwfReader $reader
     * @return self
     */
    public static function read(SwfReader $reader): self
    {
        return new DefineButtonCxformTag(
            buttonId: $reader->readUI16(),
            colorTransform: ColorTransform::read($reader, withAlpha: false),
        );
    }
}
