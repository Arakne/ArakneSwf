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

namespace Arakne\Swf\Extractor\Drawer;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Extractor\Timeline\BlendMode;
use Arakne\Swf\Parser\Structure\Record\Filter\BevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ConvolutionFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\DropShadowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GlowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientBevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientGlowFilter;
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
     * @param non-negative-int $frame The frame to draw.
     * @param list<DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter> $filters
     */
    public function include(DrawableInterface $object, Matrix $matrix, int $frame = 0, array $filters = [], BlendMode $blendMode = BlendMode::Normal): void;

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
