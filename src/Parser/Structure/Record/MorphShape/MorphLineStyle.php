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
use Arakne\Swf\Parser\SwfReader;

final readonly class MorphLineStyle
{
    public function __construct(
        public int $startWidth,
        public int $endWidth,
        public Color $startColor,
        public Color $endColor,
    ) {}

    /**
     * Read a MorphLineStyle collection.
     * The collection size is determined by the first byte read (or 3 if extended).
     *
     * @param SwfReader $reader
     * @return list<MorphLineStyle>
     */
    public static function readCollection(SwfReader $reader): array
    {
        $count = $reader->readUI8();
        $styles = [];

        if ($count === 0xff) {
            $count = $reader->readUI16();
        }

        for ($i = 0; $i < $count; $i++) {
            $styles[] = new MorphLineStyle(
                startWidth: $reader->readUI16(),
                endWidth: $reader->readUI16(),
                startColor: Color::readRgba($reader),
                endColor: Color::readRgba($reader),
            );
        }

        return $styles;
    }
}
