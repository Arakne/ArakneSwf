<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Arakne-Swf: derived from SWF.php
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Extractor\Shape;

/**
 * Shape extracted from a SWF file
 * A shapes contains multiple paths, has a size, and a position (offset)
 *
 * All values are in twips (1/20th of a pixel)
 */
final readonly class Shape
{
    public function __construct(
        public int $width,
        public int $height,
        public int $xOffset,
        public int $yOffset,

        /**
         * Path to draw, ordered by drawing order
         *
         * Note: line paths should be drawn after fill paths
         *
         * @var list<Path>
         */
        public array $paths,
    ) {}

    public function transformColors(array $colorTransform)
    {
        $newPaths = [];

        foreach ($this->paths as $path) {
            $newPaths[] = $path->transformColors($colorTransform);
        }

        return new self(
            $this->width,
            $this->height,
            $this->xOffset,
            $this->yOffset,
            $newPaths,
        );
    }
}
