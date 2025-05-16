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

namespace Arakne\Swf\Extractor\Timeline;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
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
 * Single object displayed in a frame
 */
final readonly class FrameObject
{
    public function __construct(
        /**
         * The character id of the object
         */
        public int $characterId,

        /**
         * The depth of the object
         * Object with higher depth are drawn after object with lower depth (i.e. on top of them)
         */
        public int $depth,

        /**
         * The object to draw
         *
         * Note: it may differ from the original object if a color transformation is applied
         */
        public DrawableInterface $object,

        /**
         * Bound of the object, after applying the matrix
         */
        public Rectangle $bounds,

        /**
         * The transformation matrix to apply to the object
         */
        public Matrix $matrix,

        /**
         * Color transformations to apply to the object
         *
         * @var ColorTransform[]
         */
        public array $colorTransforms = [],

        /**
         * @var list<DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter>
         */
        public array $filters = [],
        public BlendMode $blendMode = BlendMode::Normal,
    ) {}

    /**
     * Get the object to display after applying the color transformations
     */
    public function transformedObject(): DrawableInterface
    {
        $object = $this->object;

        // Apply each color transformation to the object
        // Note: it's not possible to create a single composite color transformation
        // because of clamping values to [0-255] after each transformation
        foreach ($this->colorTransforms as $transform) {
            $object = $object->transformColors($transform);
        }

        return $object;
    }

    /**
     * Apply color transformation to the object
     *
     * @param ColorTransform $colorTransform
     * @return self The new object with the color transformation applied
     */
    public function transformColors(ColorTransform $colorTransform): self
    {
        return new self(
            $this->characterId,
            $this->depth,
            $this->object,
            $this->bounds,
            $this->matrix,
            [...$this->colorTransforms, $colorTransform],
            $this->filters,
            $this->blendMode,
        );
    }

    /**
     * Modify the object properties and return a new object
     *
     * @param DrawableInterface|null $object
     * @param Rectangle|null $bounds
     * @param Matrix|null $matrix
     * @param list<DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter>|null $filters
     * @param BlendMode|null $blendMode
     *
     * @return self
     */
    public function with(
        ?DrawableInterface $object = null,
        ?Rectangle $bounds = null,
        ?Matrix $matrix = null,
        ?array $filters = null,
        ?BlendMode $blendMode = null,
    ): self {
        return new self(
            $this->characterId,
            $this->depth,
            $object ?? $this->object,
            $bounds ?? $this->bounds,
            $matrix ?? $this->matrix,
            $this->colorTransforms,
            $filters ?? $this->filters,
            $blendMode ?? $this->blendMode,
        );
    }
}
