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
use Arakne\Swf\Extractor\Shape\Path;

use function assert;
use function count;

final readonly class MorphPath
{
    public function __construct(
        private Path $start,
        private Path $end,
    ) {
        assert(count($this->start->edges) === count($this->end->edges));
    }

    /**
     * @param int<0, 65535> $ratio
     * @return Path
     */
    public function interpolate(int $ratio): Path
    {
        if ($ratio <= 0) {
            return $this->start;
        }

        if ($ratio >= RatioDrawableInterface::MAX_RATIO) {
            return $this->end;
        }

        $edges = [];

        foreach ($this->start->edges as $index => $startEdge) {
            $endEdge = $this->end->edges[$index];
            $edges[] = $startEdge->interpolate($endEdge, $ratio);
        }

        return new Path(
            $edges,
            // @todo interpolate style
            $this->start->style,
        );
    }
}
