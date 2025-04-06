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

namespace Arakne\Swf\Extractor\Drawer;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

/**
 * Base type for draw SWF shapes or sprites
 *
 * @see DrawableInterface for objects that can be drawn
 */
interface DrawerInterface
{
    /**
     * Start a new drawing area
     *
     * @param Rectangle $bounds
     */
    public function area(Rectangle $bounds): void;

    /**
     * Draw a new shape
     *
     * @param Shape $shape
     */
    public function shape(Shape $shape): void;

    /**
     * Draw a raster image
     *
     * @param ImageCharacterInterface $image
     */
    public function image(ImageCharacterInterface $image): void;

    /**
     * Include a sprite or shape in the current drawing
     *
     * @todo id parameter
     *
     * @param DrawableInterface $object
     * @param Matrix $matrix
     */
    public function include(DrawableInterface $object, Matrix $matrix): void;

    /**
     * Draw a path
     *
     * @param Path $path
     */
    public function path(Path $path): void;

    /**
     * Render the drawing
     * The returned value depends on the implementation
     */
    public function render(): mixed;
}
