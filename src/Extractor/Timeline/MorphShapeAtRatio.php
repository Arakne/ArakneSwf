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

namespace Arakne\Swf\Extractor\Timeline;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Shape\MorphShapeDefinition;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Override;

/**
 * Wrapper for MorphShapeDefinition that draws at a specific ratio.
 *
 * This is used when a morph shape is placed in a timeline with a specific ratio.
 */
final readonly class MorphShapeAtRatio implements DrawableInterface
{
    public function __construct(
        private MorphShapeDefinition $morphShape,
        private float $ratio,
    ) {}

    #[Override]
    public function bounds(): Rectangle
    {
        return $this->morphShape->boundsAtRatio($this->ratio);
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        return 1;
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        return $this->morphShape->drawAtRatio($drawer, $this->ratio);
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): DrawableInterface
    {
        // Get the shape at this ratio, then transform colors
        $shape = $this->morphShape->shapeAtRatio($this->ratio);
        $transformedShape = $shape->transformColors($colorTransform);

        // Return a simple wrapper that draws the transformed shape
        return new class($transformedShape, $this->bounds()) implements DrawableInterface {
            public function __construct(
                private readonly \Arakne\Swf\Extractor\Shape\Shape $shape,
                private readonly Rectangle $bounds,
            ) {}

            public function bounds(): Rectangle
            {
                return $this->bounds;
            }

            public function framesCount(bool $recursive = false): int
            {
                return 1;
            }

            public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
            {
                $drawer->shape($this->shape);
                return $drawer;
            }

            public function transformColors(ColorTransform $colorTransform): DrawableInterface
            {
                return new self($this->shape->transformColors($colorTransform), $this->bounds);
            }
        };
    }
}
