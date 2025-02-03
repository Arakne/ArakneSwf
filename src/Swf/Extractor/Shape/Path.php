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

use function array_map;
use function array_pop;
use function array_push;
use function array_reverse;
use function spl_object_id;

final class Path
{
    public function __construct(
        /** @var list<EdgeInterface> */
        private array $edges = [],
        public ?PathStyle $style = null,
    ) {}

    /**
     * Push new edges at the end of the path
     *
     * Note: this method will mutate the current object, so do not use outside the builder context
     *
     * @param EdgeInterface ...$edges The edges to push
     * @return $this The current object
     */
    public function push(EdgeInterface ...$edges): self
    {
        array_push($this->edges, ...$edges);

        return $this;
    }

    /**
     * Try to reconnect edges that are not connected
     * Flash seems to allow this, but SVG does not
     *
     * The order of edges will change.
     *
     * This method will not mutate the current object, but return a new one.
     */
    public function fix(): self
    {
        $edgeSet = [];

        foreach ($this->edges as $edge) {
            // @todo use a better key
            $edgeSet[spl_object_id($edge)] = $edge;
        }

        $edges = [];

        // @todo optimize
        while ($edgeSet) {
            $currentEdge = array_pop($edgeSet);
            $edges[] = $currentEdge;

            while ($currentEdge) {
                $found = false;

                foreach ($edgeSet as $other) {
                    if ($currentEdge->toX === $other->fromX && $currentEdge->toY === $other->fromY) {
                        $edges[] = $other;
                        unset($edgeSet[spl_object_id($other)]);
                        $currentEdge = $other;
                        $found = true;
                        break;
                    }

                    if ($currentEdge->toX === $other->toX && $currentEdge->toY === $other->toY) {
                        $reverse = $other->reverse();
                        $edges[] = $reverse;
                        unset($edgeSet[spl_object_id($other)]);
                        $currentEdge = $reverse;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    break;
                }
            }
        }

        $fixed = clone $this;
        $fixed->edges = $edges;

        return $fixed;
    }

    /**
     * Reverse the path and all its edges
     */
    public function reverse(): self
    {
        $reversed = clone $this;
        $reversed->edges = array_map(fn($edge) => $edge->reverse(), array_reverse($this->edges));

        return $reversed;
    }

    /**
     * Draw the current path
     */
    public function draw(PathDrawerInterface $drawer): void
    {
        $lastX = null;
        $lastY = null;

        foreach ($this->edges as $edge) {
            if (!$edge->matchFrom($lastX, $lastY)) {
                $drawer->move($edge->fromX, $edge->fromY);
            }

            $edge->draw($drawer);

            $lastX = $edge->toX;
            $lastY = $edge->toY;
        }
    }
}
