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

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\SwfTag;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Override;

/**
 * Store a single shape extracted from a SWF file
 *
 * @see DefineShapeTag
 * @see DefineShape4Tag
 */
final class ShapeDefinition implements DrawableInterface
{
    private ?Shape $shape = null;

    public function __construct(
        private ShapeProcessor $processor,

        /**
         * The character id of the shape
         *
         * @see SwfTag::$id
         */
        public readonly int $id,

        /**
         * The raw tag extracted from the SWF file
         */
        public readonly DefineShapeTag|DefineShape4Tag $tag,
    ) {}

    /**
     * Get the shape object
     * The shape is processed at first call and cached
     */
    public function shape(): Shape
    {
        if (!$this->shape) {
            $this->shape = $this->processor->process($this->tag);
            unset($this->processor); // Remove the processor to free memory
        }

        return $this->shape;
    }

    #[Override]
    public function bounds(): Rectangle
    {
        return $this->tag->shapeBounds;
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        return 1;
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        $drawer->shape($this->shape());

        return $drawer;
    }

    /**
     * Convert the shape to an SVG string
     *
     * @param bool $subpixelStrokeWidth Enable subpixel stroke width.
     *                                  If false, the minimum stroke width will be 1px to approximate Flash rendering.
     */
    public function toSvg(bool $subpixelStrokeWidth = false): string
    {
        return $this->draw(new SvgCanvas($this->bounds(), $subpixelStrokeWidth))->render();
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        $shape = $this->shape()->transformColors($colorTransform);

        $self = clone $this;
        $self->shape = $shape;

        return $self;
    }
}
