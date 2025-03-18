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

namespace Arakne\Swf\Extractor\Drawer\Svg;

use Arakne\Swf\Extractor\Shape\FillType\ClippedBitmap;
use Arakne\Swf\Extractor\Shape\FillType\LinearGradient;
use Arakne\Swf\Extractor\Shape\FillType\RadialGradient;
use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use SimpleXMLElement;

use function sprintf;

/**
 * Helper to build SVG elements
 */
final class SvgBuilder
{
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
        $g['transform'] = sprintf(
            'matrix(1, 0, 0, 1, %h, %h)',
            $xOffset / 20,
            $yOffset / 20,
        );

        return $g;
    }

    public function addPath(SimpleXMLElement $g, Path $path): SimpleXMLElement
    {
        $pathElement = $g->addChild('path');

        $this->applyFillStyle($pathElement, $path->style->fill);
        $pathElement['stroke'] = $path->style->lineColor?->hex() ?? 'none';

        if ($path->style->lineColor?->hasTransparency() === true) {
            $pathElement['stroke-opacity'] = $path->style->lineColor->opacity();
        }

        if ($path->style->lineWidth > 0) {
            $pathElement['stroke-width'] = $path->style->lineWidth / 20;
            $pathElement['stroke-linecap'] = 'round';
            $pathElement['stroke-linejoin'] = 'round';
        }

        $path->draw(new SvgPathDrawer($pathElement));

        return $pathElement;
    }

    public function applyFillStyle(SimpleXMLElement $path, Solid|LinearGradient|RadialGradient|ClippedBitmap|null $style): void
    {
        if ($style === null) {
            $path['fill'] = 'none';
            return;
        }

        $path['fill-rule'] = 'evenodd';

        match (true) {
            $style instanceof Solid => self::applyFillSolid($path, $style),
            $style instanceof LinearGradient => self::applyFillLinearGradient($path, $style),
            $style instanceof RadialGradient => self::applyFillRadialGradient($path, $style),
            $style instanceof ClippedBitmap => self::applyFillClippedBitmap($path, $style),
        };
    }

    public function applyFillSolid(SimpleXMLElement $path, Solid $style): void
    {
        $path['fill'] = $style->color->hex();

        if ($style->color->hasTransparency()) {
            $path['fill-opacity'] = $style->color->opacity();
        }
    }

    public function applyFillLinearGradient(SimpleXMLElement $path, LinearGradient $style): void
    {
        $id = 'gradient-'.$style->hash();
        $path['fill'] = 'url(#'.$id.')';

        if (isset($this->elementsById[$id])) {
            return;
        }

        $this->elementsById[$id] = $linearGradient = $this->svg->addChild('linearGradient');

        $linearGradient['gradientTransform'] = $style->matrix->toSvgTransformation();
        $linearGradient['gradientUnits'] = 'userSpaceOnUse';
        $linearGradient['spreadMethod'] = 'pad';
        $linearGradient['id'] = $id;

        // All gradients are defined in a standard space called the gradient square. The gradient square is centered at (0,0),
        // and extends from (-16384,-16384) to (16384,16384).
        $linearGradient['x1'] = '-819.2';
        $linearGradient['x2'] = '819.2';

        foreach ($style->gradient->records as $record) {
            $stop = $linearGradient->addChild('stop');
            $stop['offset'] = $record->ratio / 255;
            $stop['stop-color'] = $record->color->hex();
            $stop['stop-opacity'] = $record->color->opacity();
        }
    }

    public function applyFillRadialGradient(SimpleXMLElement $path, RadialGradient $style): void
    {
        $id = 'gradient-'.$style->hash();
        $path['fill'] = 'url(#'.$id.')';

        if (isset($this->elementsById[$id])) {
            return;
        }

        $radialGradient = $this->svg->addChild('radialGradient');

        $radialGradient['gradientTransform'] = $style->matrix->toSvgTransformation();
        $radialGradient['gradientUnits'] = 'userSpaceOnUse';
        $radialGradient['spreadMethod'] = 'pad';
        $radialGradient['id'] = $id;

        // All gradients are defined in a standard space called the gradient square. The gradient square is centered at (0,0),
        // and extends from (-16384,-16384) to (16384,16384).
        $radialGradient['cx'] = '0';
        $radialGradient['cy'] = '0';
        $radialGradient['r'] = '819.2';

        foreach ($style->gradient->records as $record) {
            $stop = $radialGradient->addChild('stop');
            $stop['offset'] = $record->ratio / 255;
            $stop['stop-color'] = $record->color->hex();

            if ($record->color->hasTransparency()) {
                $stop['stop-opacity'] = $record->color->opacity();
            }
        }
    }

    public function applyFillClippedBitmap(SimpleXMLElement $path, ClippedBitmap $style): void
    {
        $pattern = $this->svg->addChild('pattern');

        $pattern['id'] = 'pattern-'.$style->hash();
        $pattern['overflow'] = 'visible';
        $pattern['patternUnits'] = 'userSpaceOnUse';
        $pattern['width'] = $style->bitmap->bounds()->width() / 20;
        $pattern['height'] = $style->bitmap->bounds()->height() / 20;
        $pattern['viewBox'] = sprintf('0 0 %h %h', $style->bitmap->bounds()->width() / 20, $style->bitmap->bounds()->height() / 20);
        $pattern['patternTransform'] = $style->matrix->toSvgTransformation(undoTwipScale: true);

        $image = $pattern->addChild('image');
        $image['href'] = $style->bitmap->toBase64Data();

        $path['fill'] = 'url(#'.$pattern['id'].')';
    }
}
