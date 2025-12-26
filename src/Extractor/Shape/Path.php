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
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Parser\Structure\Record\ColorTransform;

use function array_key_first;
use function spl_object_id;

/**
 * Structure for a polygon or line path
 *
 * Note: this structure is not immutable, so be careful when using it
 */
final class Path
{
    public function __construct(
        /** @var list<EdgeInterface> */
        public private(set) array $edges,
        public readonly PathStyle $style,
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
        foreach ($edges as $edge) {
            $this->edges[] = $edge;
        }

        return $this;
    }

    /**
     * Try to reconnect edges that are not connected
     * Flash seems to allow this, but SVG does not
     *
     * The order of edges will change.
     *
     * This method will not mutate the current object, but return a new one.
     *
     * Algorithm:
     * 1. Create a set of edges (will be used to keep trace of remaining edges)
     * 2. Pop the first edge from the set if not empty
     * 3. Push this edge to the result
     * 4. Find the next edge, from the set, that is connected to the current one. An edge is considered as connected if:
     *    - the `to` point of the current edge is the `from` point of the next edge
     *    - the `to` point of the current edge is the `to` point of the next edge. In this case the edge is reversed
     * 5. Push this edge to the result, remove it from the set, define as current edge, and go to step 4
     * 6. If no edge is found, go to step 2 (handle disconnected paths)
     * 7. When the set is empty, the algorithm is finished, so return a new instance of Path with the fixed edges
     */
    public function fix(): self
    {
        $edgeSet = [];

        foreach ($this->edges as $edge) {
            $edgeSet[spl_object_id($edge)] = $edge;
        }

        $edges = [];

        while ($edgeSet) {
            $currentEdgeKey = array_key_first($edgeSet);
            $currentEdge = $edgeSet[$currentEdgeKey];
            unset($edgeSet[$currentEdgeKey]);
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
     * Draw the current path
     */
    public function draw(PathDrawerInterface $drawer): void
    {
        $lastX = null;
        $lastY = null;

        foreach ($this->edges as $edge) {
            if ($edge->fromX !== $lastX || $edge->fromY !== $lastY) {
                $drawer->move($edge->fromX, $edge->fromY);
            }

            $edge->draw($drawer);

            $lastX = $edge->toX;
            $lastY = $edge->toY;
        }

        $drawer->draw();
    }

    public function transformColors(ColorTransform $colorTransform): self
    {
        return new self(
            $this->edges,
            $this->style->transformColors($colorTransform),
        );
    }
}
