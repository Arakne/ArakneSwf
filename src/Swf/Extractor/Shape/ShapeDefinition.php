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

use Arakne\Swf\Extractor\Shape\Svg\SvgCanvas;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\SwfTagPosition;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;

/**
 * Store a single shape extracted from a SWF file
 *
 * @todo drawable interface
 */
final class ShapeDefinition
{
    /**
     * The parsed shape
     * This is a computed property, and the shape will be processed only when requested
     */
    public private(set) Shape $shape {
        get => $this->shape ??= $this->processor->process($this->tag);
    }

    public Rectangle $bounds {
        get => $this->tag->shapeBounds;
    }

    public function __construct(
        private readonly ShapeProcessor $processor,

        /**
         * The character id of the shape
         *
         * @see SwfTagPosition::$id
         */
        public readonly int $id,

        /**
         * The raw tag extracted from the SWF file
         */
        public readonly DefineShapeTag|DefineShape4Tag $tag,
    ) {}

    public function draw(SvgCanvas $canvas): SvgCanvas
    {
        $canvas->shape($this->shape);

        return $canvas;
    }

    /**
     * Convert the shape to an SVG string
     */
    public function toSvg(): string
    {
        return $this->draw(new SvgCanvas($this->bounds))->toXml();
    }

    // @todo object
    public function transformColors(array $colorTransform): self
    {
        $shape = $this->shape->transformColors($colorTransform);

        $self = clone $this;
        $self->shape = $shape;

        return $self;
    }
}
