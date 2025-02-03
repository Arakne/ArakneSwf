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
 * Build paths of a shape
 * This builder will associate styles to paths and merge them when possible
 */
final class PathsBuilder
{
    /**
     * @var array<string, Path>
     */
    private array $openPaths = [];

    /**
     * @var list<Path>
     */
    private array $closedPaths = [];

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
     */
    public function setActiveStyles(?PathStyle ...$styles): void
    {
        $this->activeStyles = $styles;
    }

    /**
     * Merge new edges to all open paths with the active styles
     */
    public function merge(EdgeInterface ...$edges): void
    {
        foreach ($this->activeStyles as $style) {
            if ($style === null) {
                continue;
            }

            $lastPath = $this->openPaths[$style->hash()] ?? null;

            if (!$lastPath) {
                $this->openPaths[$style->hash()] = new Path($edges, $style);
            } else {
                $this->openPaths[$style->hash()] = $lastPath->push(...$edges);
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
     * Export all built paths
     *
     * @return list<Path>
     */
    public function export(): array
    {
        $this->close();

        $paths = [];

        foreach ($this->closedPaths as $path) {
            $paths[] = $path->fix();
        }

        return $paths;
    }
}
