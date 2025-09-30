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

namespace Arakne\Swf\Extractor\Drawer\Svg;

use Arakne\Swf\Extractor\Drawer\Svg\Filter\SvgFilterBuilder;
use Arakne\Swf\Extractor\Shape\FillType\Bitmap;
use Arakne\Swf\Extractor\Shape\FillType\LinearGradient;
use Arakne\Swf\Extractor\Shape\FillType\RadialGradient;
use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Parser\Structure\Record\Filter\BevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ConvolutionFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\DropShadowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GlowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientBevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientGlowFilter;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use SimpleXMLElement;

use function assert;
use function sprintf;

/**
 * Helper to build SVG elements
 */
final class SvgBuilder
{
    public const string XLINK_NS = 'http://www.w3.org/1999/xlink';

    /**
     * @var array<string, SimpleXmlElement>
     */
    private array $elementsById = [];

    public function __construct(
        /**
         * The SVG element to draw on
         * It should be the root or the defs element
         */
        private readonly SimpleXMLElement $svg,

        /**
         * Enable subpixel stroke width (default: true)
         *
         * If true, the stroke width will be according to the actual SWF stroke width (can be a float value).
         * In this case, stroke below 1px will be rendered by the antialiasing of the renderer, so in a blurry and non-opaque way.
         * This rendering differs from flash, which always render stroke with a minimum of 1px width.
         *
         * If false, the minimum stroke width will be 1px, and `non-scaling-stroke` will be used to avoid stroke scaling
         * when the SVG is resized.
         * This allows to approximate the flash rendering at native size, but the relative stroke width will not be preserved,
         * so rescaling will not be accurate.
         */
        public readonly bool $subpixelStrokeWidth = true,
    ) {}

    public function addGroup(Rectangle $bounds): SimpleXMLElement
    {
        return $this->addGroupWithOffset(
            -$bounds->xmin,
            -$bounds->ymin,
        );
    }

    public function addGroupWithOffset(int $xOffset, int $yOffset): SimpleXMLElement
    {
        $g = $this->svg->addChild('g');
        $g->addAttribute('transform', sprintf(
            'matrix(1, 0, 0, 1, %h, %h)',
            $xOffset / 20,
            $yOffset / 20,
        ));

        return $g;
    }

    public function addPath(SimpleXMLElement $g, Path $path): SimpleXMLElement
    {
        $pathElement = $g->addChild('path');

        $this->applyFillStyle($pathElement, $path->style->fill, 'fill');

        if ($path->style->lineFill !== null) {
            $this->applyFillStyle($pathElement, $path->style->lineFill, 'stroke');
        } else {
            $pathElement->addAttribute('stroke', $path->style->lineColor?->hex() ?? 'none');

            if ($path->style->lineColor?->hasTransparency() === true) {
                $pathElement->addAttribute('stroke-opacity', (string) $path->style->lineColor->opacity());
            }
        }

        if ($path->style->lineWidth > 0) {
            $width = $path->style->lineWidth / 20;

            if (!$this->subpixelStrokeWidth && $width < 1) {
                $width = 1;
                $pathElement->addAttribute('vector-effect', 'non-scaling-stroke');
            }

            $pathElement->addAttribute('stroke-width', (string) $width);
            $pathElement->addAttribute('stroke-linecap', 'round'); // @todo use style from LINESTYLE2 if available
            $pathElement->addAttribute('stroke-linejoin', 'round');
        }

        $path->draw(new SvgPathDrawer($pathElement));

        return $pathElement;
    }

    /**
     * @param list<DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter> $filters
     * @param string $id The id of the filter element to create
     */
    public function addFilter(array $filters, string $id, float $width, float $height): void
    {
        $filterBuilder = SvgFilterBuilder::create($this->svg, $id, $width, $height);

        foreach ($filters as $filter) {
            $filterBuilder->apply($filter);
        }

        $filterBuilder->finalize();
    }

    /**
     * @param SimpleXMLElement $path The path element to configure
     * @param Solid|LinearGradient|RadialGradient|Bitmap|null $style The fill style to apply
     * @param string $attribute The attribute to use for the fill style. Should be 'fill' or 'stroke'
     * @return void
     */
    public function applyFillStyle(SimpleXMLElement $path, Solid|LinearGradient|RadialGradient|Bitmap|null $style, string $attribute): void
    {
        if ($style === null) {
            $path->addAttribute($attribute, 'none');
            return;
        }

        if ($attribute === 'fill') {
            $path->addAttribute('fill-rule', 'evenodd');
        }

        match (true) {
            $style instanceof Solid => self::applyFillSolid($path, $style, $attribute),
            $style instanceof LinearGradient => self::applyFillLinearGradient($path, $style, $attribute),
            $style instanceof RadialGradient => self::applyFillRadialGradient($path, $style, $attribute),
            $style instanceof Bitmap => self::applyFillClippedBitmap($path, $style, $attribute),
        };
    }

    public function applyFillSolid(SimpleXMLElement $path, Solid $style, string $attribute): void
    {
        $path->addAttribute($attribute, $style->color->hex());

        if ($style->color->hasTransparency()) {
            $path->addAttribute($attribute.'-opacity', (string) $style->color->opacity());
        }
    }

    public function applyFillLinearGradient(SimpleXMLElement $path, LinearGradient $style, string $attribute): void
    {
        $id = 'gradient-'.$style->hash();
        $path->addAttribute($attribute, 'url(#'.$id.')');

        if (isset($this->elementsById[$id])) {
            return;
        }

        $this->elementsById[$id] = $linearGradient = $this->svg->addChild('linearGradient');
        assert($linearGradient instanceof SimpleXMLElement);

        $linearGradient->addAttribute('gradientTransform', $style->matrix->toSvgTransformation());
        $linearGradient->addAttribute('gradientUnits', 'userSpaceOnUse');
        $linearGradient->addAttribute('spreadMethod', 'pad');
        $linearGradient->addAttribute('id', $id);

        // All gradients are defined in a standard space called the gradient square. The gradient square is centered at (0,0),
        // and extends from (-16384,-16384) to (16384,16384).
        $linearGradient->addAttribute('x1', '-819.2');
        $linearGradient->addAttribute('x2', '819.2');

        foreach ($style->gradient->records as $record) {
            $stop = $linearGradient->addChild('stop');
            $stop->addAttribute('offset', (string) ($record->ratio / 255));
            $stop->addAttribute('stop-color', $record->color->hex());
            $stop->addAttribute('stop-opacity', (string) $record->color->opacity());
        }
    }

    public function applyFillRadialGradient(SimpleXMLElement $path, RadialGradient $style, string $attribute): void
    {
        $id = 'gradient-'.$style->hash();
        $path->addAttribute($attribute, 'url(#'.$id.')');

        if (isset($this->elementsById[$id])) {
            return;
        }

        $radialGradient = $this->svg->addChild('radialGradient');
        assert($radialGradient instanceof SimpleXMLElement);

        $radialGradient->addAttribute('gradientTransform', $style->matrix->toSvgTransformation());
        $radialGradient->addAttribute('gradientUnits', 'userSpaceOnUse');
        $radialGradient->addAttribute('spreadMethod', 'pad');
        $radialGradient->addAttribute('id', $id);

        // All gradients are defined in a standard space called the gradient square. The gradient square is centered at (0,0),
        // and extends from (-16384,-16384) to (16384,16384).
        $radialGradient->addAttribute('cx', '0');
        $radialGradient->addAttribute('cy', '0');
        $radialGradient->addAttribute('r', '819.2');

        if ($style->gradient->focalPoint) {
            $radialGradient->addAttribute('fx', '0');
            $radialGradient->addAttribute('fy', (string) ($style->gradient->focalPoint * 819.2));
        }

        foreach ($style->gradient->records as $record) {
            $stop = $radialGradient->addChild('stop');
            $stop->addAttribute('offset', (string) ($record->ratio / 255));
            $stop->addAttribute('stop-color', $record->color->hex());

            if ($record->color->hasTransparency()) {
                $stop->addAttribute('stop-opacity', (string) $record->color->opacity());
            }
        }
    }

    public function applyFillClippedBitmap(SimpleXMLElement $path, Bitmap $style, string $attribute): void
    {
        $id = 'pattern-'.$style->hash();
        $path->addAttribute($attribute, 'url(#'.$id.')');

        if (isset($this->elementsById[$id])) {
            return;
        }

        $this->elementsById[$id] = $pattern = $this->svg->addChild('pattern');
        assert($pattern instanceof SimpleXMLElement);

        $pattern->addAttribute('id', $id);
        $pattern->addAttribute('overflow', 'visible');
        $pattern->addAttribute('patternUnits', 'userSpaceOnUse');
        $pattern->addAttribute('width', (string) ($style->bitmap->bounds()->width() / 20));
        $pattern->addAttribute('height', (string) ($style->bitmap->bounds()->height() / 20));
        $pattern->addAttribute('viewBox', sprintf('0 0 %h %h', $style->bitmap->bounds()->width() / 20, $style->bitmap->bounds()->height() / 20));
        $pattern->addAttribute('patternTransform', $style->matrix->toSvgTransformation(undoTwipScale: true));

        if (!$style->smoothed) {
            $pattern->addAttribute('image-rendering', 'optimizeSpeed');
        }

        $b64 = $style->bitmap->toBase64Data();
        $imageId = 'image-'.md5($b64);

        if (!isset($this->elementsById[$imageId])) {
            $this->elementsById[$imageId] = $image = $pattern->addChild('image');
            $image->addAttribute('xlink:href', $b64, self::XLINK_NS);
            $image->addAttribute('id', $imageId);
        } else {
            $use = $pattern->addChild('use');
            $use->addAttribute('xlink:href', '#'.$imageId, self::XLINK_NS);
        }
    }
}
