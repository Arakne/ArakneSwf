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

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\SwfTag;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShape2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShapeTag;
use Override;
use WeakMap;

/**
 * Store a morph shape extracted from a SWF file.
 *
 * MorphShapes define two states (start and end) and can be rendered at any
 * interpolation ratio between them. The ratio can be controlled via the frame parameter
 * in draw() method or explicitly via shapeAtRatio().
 *
 * Frame 0 = start state (ratio 0.0)
 * Frame 65535 = end state (ratio 1.0)
 *
 * @see DefineMorphShapeTag
 * @see DefineMorphShape2Tag
 */
final class MorphShapeDefinition implements DrawableInterface
{
    /**
     * Cache of processed shapes at specific ratios.
     * Using WeakMap would be ideal but we need float keys, so using array.
     *
     * @var array<string, Shape>
     */
    private array $shapeCache = [];

    public function __construct(
        private MorphShapeProcessor $processor,

        /**
         * The character id of the shape
         *
         * @see SwfTag::$id
         */
        public readonly int $id,

        /**
         * The raw tag extracted from the SWF file
         */
        public readonly DefineMorphShapeTag|DefineMorphShape2Tag $tag,
    ) {}

    /**
     * Get the shape object at a specific interpolation ratio.
     *
     * @param float $ratio The interpolation ratio between 0.0 (start) and 1.0 (end)
     *
     * @return Shape The interpolated shape
     */
    public function shapeAtRatio(float $ratio): Shape
    {
        $ratio = max(0.0, min(1.0, $ratio));
        $cacheKey = sprintf('%.4f', $ratio);

        if (!isset($this->shapeCache[$cacheKey])) {
            $this->shapeCache[$cacheKey] = $this->processor->process($this->tag, $ratio);
        }

        return $this->shapeCache[$cacheKey];
    }

    /**
     * Get the start shape (ratio 0.0).
     */
    public function startShape(): Shape
    {
        return $this->shapeAtRatio(0.0);
    }

    /**
     * Get the end shape (ratio 1.0).
     */
    public function endShape(): Shape
    {
        return $this->shapeAtRatio(1.0);
    }

    /**
     * Get the bounds at a specific ratio.
     * Returns interpolated bounds between start and end.
     */
    public function boundsAtRatio(float $ratio): Rectangle
    {
        $ratio = max(0.0, min(1.0, $ratio));

        $start = $this->tag->startBounds;
        $end = $this->tag->endBounds;

        return new Rectangle(
            xmin: (int) round($start->xmin + ($end->xmin - $start->xmin) * $ratio),
            xmax: (int) round($start->xmax + ($end->xmax - $start->xmax) * $ratio),
            ymin: (int) round($start->ymin + ($end->ymin - $start->ymin) * $ratio),
            ymax: (int) round($start->ymax + ($end->ymax - $start->ymax) * $ratio),
        );
    }

    #[Override]
    public function bounds(): Rectangle
    {
        // Return the union of start and end bounds to ensure all frames fit
        $start = $this->tag->startBounds;
        $end = $this->tag->endBounds;

        return new Rectangle(
            xmin: min($start->xmin, $end->xmin),
            xmax: max($start->xmax, $end->xmax),
            ymin: min($start->ymin, $end->ymin),
            ymax: max($start->ymax, $end->ymax),
        );
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        // MorphShapes are single-frame characters that interpolate based on ratio.
        // The actual animation frames come from the timeline that contains the morph.
        // When placed in a timeline, the PlaceObject tag's ratio field controls interpolation.
        return 1;
    }

    /**
     * Draw the morph shape at the given frame.
     *
     * Frame 0 = start state (ratio 0.0)
     * Frame 65535 = end state (ratio 1.0)
     *
     * @param DrawerInterface $drawer
     * @param non-negative-int $frame Frame number (0-65535)
     *
     * @return DrawerInterface
     */
    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        $ratio = min($frame, 65535) / 65535.0;
        $drawer->shape($this->shapeAtRatio($ratio));

        return $drawer;
    }

    /**
     * Draw the morph shape at a specific ratio.
     *
     * @param DrawerInterface $drawer
     * @param float $ratio Interpolation ratio between 0.0 (start) and 1.0 (end)
     *
     * @return DrawerInterface
     */
    public function drawAtRatio(DrawerInterface $drawer, float $ratio): DrawerInterface
    {
        $drawer->shape($this->shapeAtRatio($ratio));

        return $drawer;
    }

    /**
     * Convert the morph shape to an SVG string at a specific ratio.
     *
     * @param float $ratio Interpolation ratio between 0.0 (start) and 1.0 (end)
     * @param bool $subpixelStrokeWidth Enable subpixel stroke width.
     *
     * @return string SVG string
     */
    public function toSvgAtRatio(float $ratio, bool $subpixelStrokeWidth = false): string
    {
        return $this->drawAtRatio(new SvgCanvas($this->boundsAtRatio($ratio), $subpixelStrokeWidth), $ratio)->render();
    }

    /**
     * Convert the start shape to an SVG string.
     *
     * @param bool $subpixelStrokeWidth Enable subpixel stroke width.
     */
    public function toSvg(bool $subpixelStrokeWidth = false): string
    {
        return $this->toSvgAtRatio(0.0, $subpixelStrokeWidth);
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        // For color transforms, we need to create a new definition that applies
        // the transform at render time. Since shapes are cached, we need to
        // transform the cached shapes.
        $self = clone $this;
        $self->shapeCache = [];

        // We can't easily transform a MorphShapeDefinition because the processor
        // generates shapes on demand. Instead, we'll cache transformed shapes.
        // This is a simplified approach - a more complete solution would involve
        // a wrapper or modified processor.

        foreach ($this->shapeCache as $key => $shape) {
            $self->shapeCache[$key] = $shape->transformColors($colorTransform);
        }

        return $self;
    }

    /**
     * Clear the shape cache to free memory.
     */
    public function clearCache(): void
    {
        $this->shapeCache = [];
    }
}
