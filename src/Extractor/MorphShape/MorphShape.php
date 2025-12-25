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

namespace Arakne\Swf\Extractor\MorphShape;

use Arakne\Swf\Extractor\RatioDrawableInterface;
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

final readonly class MorphShape
{
    public const int MAX_RATIO = RatioDrawableInterface::MAX_RATIO;

    public function __construct(
        public Rectangle $startBounds,
        public Rectangle $endBounds,

        /**
         * @var list<MorphPath>
         */
        public array $paths,
    ) {}

    /**
     * @param int<0, 65535> $ratio
     */
    public function interpolate(int $ratio): Shape
    {
        $bounds = $this->bounds($ratio);
        $paths = [];

        foreach ($this->paths as $morphPath) {
            $paths[] = $morphPath->interpolate($ratio);
        }

        return new Shape(
            $bounds->width(),
            $bounds->height(),
            -$bounds->xmin,
            -$bounds->ymin,
            $paths,
        );
    }

    /**
     * Compute shape bounds at the given ratio
     *
     * @param int<0, 65535> $ratio
     * @return Rectangle
     */
    public function bounds(int $ratio): Rectangle
    {
        if ($ratio <= 0) {
            return $this->startBounds;
        }

        if ($ratio >= self::MAX_RATIO) {
            return $this->endBounds;
        }

        return new Rectangle(
            self::interpolateInt($this->startBounds->xmin, $this->endBounds->xmin, $ratio),
            self::interpolateInt($this->startBounds->xmax, $this->endBounds->xmax, $ratio),
            self::interpolateInt($this->startBounds->ymin, $this->endBounds->ymin, $ratio),
            self::interpolateInt($this->startBounds->ymax, $this->endBounds->ymax, $ratio),
        );
    }

    public static function interpolateInt(int $start, int $end, int $ratio): int
    {
        return (int) (($start * (self::MAX_RATIO - $ratio) + $end * $ratio) / self::MAX_RATIO);
    }
}
