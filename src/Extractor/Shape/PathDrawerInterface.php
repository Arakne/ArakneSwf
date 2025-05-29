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

/**
 * Draw a single path
 * Objects implementing this interface are stateful and should be used only once
 *
 * Coordinates are in twips (1/20th of a pixel)
 */
interface PathDrawerInterface
{
    /**
     * Move the cursor to the given position
     */
    public function move(int $x, int $y): void;

    /**
     * Draw a line from the current cursor position to the given position, and update the cursor position
     */
    public function line(int $toX, int $toY): void;

    /**
     * Draw a curve from the current cursor position to the given position, and update the cursor position
     */
    public function curve(int $controlX, int $controlY, int $toX, int $toY): void;

    /**
     * Finalize the path and draw it
     */
    public function draw(): void;
}
