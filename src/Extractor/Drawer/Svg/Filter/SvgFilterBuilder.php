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

namespace Arakne\Swf\Extractor\Drawer\Svg\Filter;

use Arakne\Swf\Parser\Structure\Record\Filter\BevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ConvolutionFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\DropShadowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GlowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientBevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientGlowFilter;
use SimpleXMLElement;

/**
 * Builds SVG filters for Flash filters.
 *
 * This class is stateful, and directly modifies the SVG filter element.
 * So it must be recreated for each filter.
 */
final class SvgFilterBuilder
{
    private int $filterCount = 0;
    private string $lastResult = 'SourceGraphic';
    private float $xOffset = 0;
    private float $yOffset = 0;

    private function __construct(
        private readonly SimpleXMLElement $filter,
        private readonly float $width,
        private readonly float $height,
    ) {}

    /**
     * Apply a new filter to the current filter builder.
     */
    public function apply(DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter $filter): void
    {
        $this->lastResult = match (true) {
            $filter instanceof ColorMatrixFilter => SvgColorMatrixFilter::apply($this, $filter, $this->lastResult),
            $filter instanceof BlurFilter => SvgBlurFilter::apply($this, $filter, $this->lastResult),
            $filter instanceof GlowFilter => SvgGlowFilter::apply($this, $filter, $this->lastResult),
            $filter instanceof DropShadowFilter => SvgDropShadowFilter::apply($this, $filter, $this->lastResult),
            default => throw new \RuntimeException('Unsupported filter type: ' . $filter::class),
        };
    }

    /**
     * Create a new filter element.
     *
     * @param string $element The name of the filter element to create.
     * @param string|null $in The "in" attribute of the filter element. If null, no attribute is added.
     *
     * @return SimpleXMLElement
     */
    public function addFilter(string $element, ?string $in = null): SimpleXMLElement
    {
        $filterElement = $this->filter->addChild($element);

        if ($in) {
            $filterElement->addAttribute('in', $in);
        }

        return $filterElement;
    }

    /**
     * Create a new filter element and add a result attribute to it.
     *
     * @param string $element The name of the filter element to create.
     * @param string|null $in The "in" attribute of the filter element. If null, no attribute is added.
     *
     * @return list{SimpleXMLElement, string} The filter element and the result ID
     */
    public function addResultFilter(string $element, ?string $in = null): array
    {
        $filterElement = $this->addFilter($element, $in);
        $filterElement->addAttribute('result', $result = 'filter' . ++$this->filterCount);

        $filterElement->addAttribute('id', $result);

        return [$filterElement, $result];
    }

    /**
     * Increase the offset of the filter element.
     * The width and height of the filter will also be increased by the given offsets.
     */
    public function addOffset(float $x, float $y): void
    {
        $this->xOffset += $x;
        $this->yOffset += $y;
    }

    /**
     * Apply computed properties to the filter element.
     * Must be called after all filters have been applied.
     */
    public function finalize(): void
    {
        if ($this->xOffset > 0 || $this->yOffset > 0) {
            $this->filter->addAttribute('width', (string) ($this->width + $this->xOffset * 2));
            $this->filter->addAttribute('height', (string) ($this->height + $this->yOffset * 2));
            $this->filter->addAttribute('x', (string) -$this->xOffset);
            $this->filter->addAttribute('y', (string) -$this->yOffset);
        }
    }

    /**
     * Create a new filter builder, and its corresponding filter element.
     *
     * @param SimpleXMLElement $root The root element to which the filter will be added.
     * @param string $id The ID of the filter element to create.
     *
     * @return self
     */
    public static function create(SimpleXMLElement $root, string $id, float $width, float $height): self
    {
        $filter = $root->addChild('filter');
        $filter->addAttribute('id', $id);
        $filter->addAttribute('filterUnits', 'userSpaceOnUse'); // Allow overflow

        return new self($filter, $width, $height);
    }
}
