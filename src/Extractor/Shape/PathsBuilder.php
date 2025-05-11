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

use function array_map;
use function array_reverse;

/**
 * Build paths of a shape
 * This builder will associate styles to paths and merge them when possible
 */
final class PathsBuilder
{
    /**
     * Paths that are in the process of being built
     *
     * @var array<string, Path>
     */
    private array $openPaths = [];

    /**
     * All paths that have been built, so no more edges can be added
     *
     * Note: those paths are not yet finalized (i.e. not yet fixed nor ordered)
     *
     * @var list<Path>
     */
    private array $closedPaths = [];

    /**
     * All paths ready to be exported
     * Those paths are already fixed and ordered (i.e. polygon are properly closed, and lines paths are placed after fill paths)
     *
     * @var list<Path>
     */
    private array $finalizedPaths = [];

    /**
     * @var list<PathStyle|null>
     */
    private array $activeStyles = [];

    /**
     * Active styles that will be used to draw paths
     * If null is passed, the style will be ignored
     *
     * @param PathStyle|null ...$styles
     * @return void
     * @no-named-arguments
     */
    public function setActiveStyles(?PathStyle ...$styles): void
    {
        $this->activeStyles = $styles;
    }

    /**
     * Merge new edges to all open paths with the active styles
     *
     * @no-named-arguments
     */
    public function merge(EdgeInterface ...$edges): void
    {
        foreach ($this->activeStyles as $style) {
            if ($style === null) {
                continue;
            }

            $toPush = $style->reverse ? $this->reserveEdges($edges) : $edges;
            $lastPath = $this->openPaths[$style->hash()] ?? null;

            if (!$lastPath) {
                $this->openPaths[$style->hash()] = new Path($toPush, $style);
            } else {
                $this->openPaths[$style->hash()] = $lastPath->push(...$toPush);
            }
        }
    }

    /**
     * Close all active paths
     * This method should be called when the drawing context changes (e.g. new styles)
     *
     * Note: this method is automatically called by {@see export()}
     */
    public function close(): void
    {
        foreach ($this->openPaths as $path) {
            $this->closedPaths[] = $path;
        }
        $this->openPaths = [];
    }

    /**
     * Finalize drawing of all active paths
     * This allows to start a new drawing context
     */
    public function finalize(): void
    {
        $this->finalizedPaths = $this->export();
        $this->closedPaths = [];
    }

    /**
     * Export all built paths
     *
     * @return list<Path>
     */
    public function export(): array
    {
        $this->close();

        $fillPaths = [];
        $linePaths = [];

        foreach ($this->closedPaths as $path) {
            $fixedPath = $path->fix();

            if ($fixedPath->style->lineWidth > 0) {
                $linePaths[] = $fixedPath;
            } else {
                $fillPaths[] = $fixedPath;
            }
        }

        // Line paths should be drawn after fill paths
        return [...$this->finalizedPaths, ...$fillPaths, ...$linePaths];
    }

    /**
     * Reverse edges, and reverse the order
     *
     * @param list<EdgeInterface> $edges
     * @return list<EdgeInterface>
     */
    private function reserveEdges(array $edges): array
    {
        $reversed = [];

        foreach (array_reverse($edges) as $edge) {
            $reversed[] = $edge->reverse();
        }

        return $reversed;
    }
}
