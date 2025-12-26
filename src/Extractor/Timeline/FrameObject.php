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
use Arakne\Swf\Extractor\MorphShape\MorphShape;
use Arakne\Swf\Extractor\RatioDrawableInterface;
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
         * Color transformation property to apply to the object.
         */
        public ?ColorTransform $colorTransform = null,

        /**
         * Define the current object as a clipping mask.
         *
         * All objects from the current object's depth to the depth defined by this property
         * will be clipped following the current object's shape (i.e. displayed only inside the current object's shape).
         *
         * If this value is not null, the current object must not be displayed.
         *
         * @var int|null
         */
        public ?int $clipDepth = null,

        /**
         * The name of the object.
         *
         * It must be unique in the current frame.
         * This value can be used to reference the object, allowing to retrieve and modify it later.
         */
        public ?string $name = null,

        /**
         * @var list<DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter>
         */
        public array $filters = [],
        public BlendMode $blendMode = BlendMode::Normal,

        /**
         * The ratio for morphing objects.
         * This value will be between 0 and 65535, where 0 means the start shape, and 65535 the end shape.
         *
         * @var int<0, 65535>|null
         * @see MorphShape::MAX_RATIO
         */
        public ?int $ratio = null,

        /**
         * Color transformations to apply to the object
         *
         * This property is fill by the `transformColors()` method,
         * and only used internally to apply lazily the color transformations, which can be costly when transformations
         * are applied recursively on sprites.
         *
         * It should not be confused with the `colorTransform` property,
         * which is always applied first, and can be changed by the `with()` method, in context of a PlaceObjectXTag
         * with move flag set to true.
         *
         * @var ColorTransform[]
         */
        private array $colorTransforms = [],
    ) {}

    /**
     * Get the object to display after applying the color transformations
     */
    public function transformedObject(): DrawableInterface
    {
        $object = $this->object;

        if ($this->colorTransform) {
            $object = $object->transformColors($this->colorTransform);
        }

        // Apply each color transformation to the object
        // Note: it's not possible to create a single composite color transformation
        // because of clamping values to [0-255] after each transformation
        foreach ($this->colorTransforms as $transform) {
            $object = $object->transformColors($transform);
        }

        if ($this->ratio !== null && $object instanceof RatioDrawableInterface) {
            $object = $object->withRatio($this->ratio);
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
            $this->depth,
            $this->object,
            $this->bounds,
            $this->matrix,
            $this->colorTransform,
            $this->clipDepth,
            $this->name,
            $this->filters,
            $this->blendMode,
            $this->ratio,
            [...$this->colorTransforms, $colorTransform],
        );
    }

    /**
     * Modify the object properties and return a new object
     *
     * @param DrawableInterface|null $object
     * @param Rectangle|null $bounds
     * @param Matrix|null $matrix
     * @param ColorTransform|null $colorTransform
     * @param list<DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter>|null $filters
     * @param BlendMode|null $blendMode
     * @param int|null $clipDepth
     * @param string|null $name
     * @param int<0, 65535>|null $ratio
     *
     * @return self
     */
    public function with(
        ?DrawableInterface $object = null,
        ?Rectangle $bounds = null,
        ?Matrix $matrix = null,
        ?ColorTransform $colorTransform = null,
        ?array $filters = null,
        ?BlendMode $blendMode = null,
        ?int $clipDepth = null,
        ?string $name = null,
        ?int $ratio = null,
    ): self {
        return new self(
            $this->depth,
            $object ?? $this->object,
            $bounds ?? $this->bounds,
            $matrix ?? $this->matrix,
            $colorTransform ?? $this->colorTransform,
            $clipDepth ?? $this->clipDepth,
            $name ?? $this->name,
            $filters ?? $this->filters,
            $blendMode ?? $this->blendMode,
            $ratio ?? $this->ratio,
            $this->colorTransforms,
        );
    }
}
