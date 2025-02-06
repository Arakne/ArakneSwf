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

namespace Arakne\Swf\Extractor\Shape\Svg;

use Arakne\Swf\Extractor\Shape\FillType\LinearGradient;
use Arakne\Swf\Extractor\Shape\FillType\RadialGradient;
use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Extractor\Shape\Shape;
use InvalidArgumentException;
use SimpleXMLElement;

use function sprintf;

final readonly class SvgShapeDrawer
{
    public function draw(Shape $shape): string
    {
        return $this->drawXml($shape)->asXML();
    }

    public function drawXml(Shape $shape): SimpleXMLElement
    {
        $xml = new SimpleXMLElement('<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $xml->addAttribute('width', ($shape->width / 20).'px');
        $xml->addAttribute('height', ($shape->height / 20).'px');

        $g = $xml->addChild('g');
        $g->addAttribute('transform', sprintf(
            'matrix(1.0, 0.0, 0.0, 1, %h, %h)',
            $shape->xOffset / 20,
            $shape->yOffset / 20,
        ));

        foreach ($shape->paths as $path) {
            $pathElement = $g->addChild('path');

            $this->applyFillStyle($xml, $pathElement, $path->style->fill);
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
        }

        return $xml;
    }

    private function applyFillStyle(SimpleXMLElement $svg, SimpleXMLElement $path, Solid|LinearGradient|RadialGradient|null $style): void
    {
        if ($style === null) {
            $path['fill'] = 'none';
            return;
        }

        $path['fill-rule'] = 'evenodd';

        match (true) {
            $style instanceof Solid => $this->applyFillSolid($path, $style),
            $style instanceof LinearGradient => $this->applyFillLinearGradient($svg, $path, $style),
            $style instanceof RadialGradient => $this->applyFillRadialGradient($svg, $path, $style),
            default => throw new InvalidArgumentException('Unknown fill style: '.$style::class),
        };
    }

    private function applyFillSolid(SimpleXMLElement $path, Solid $style): void
    {
        $path['fill'] = $style->color->hex();

        if ($style->color->hasTransparency()) {
            $path['fill-opacity'] = $style->color->opacity();
        }
    }

    private function applyFillLinearGradient(SimpleXMLElement $svg, SimpleXMLElement $path, LinearGradient $style)
    {
        $linearGradient = $svg->addChild('linearGradient');

        $linearGradient['gradientTransform'] = $style->matrix->toSvgTransformation();
        $linearGradient['gradientUnits'] = 'userSpaceOnUse';
        $linearGradient['spreadMethod'] = 'pad';
        $linearGradient['id'] = 'gradient-'.$style->hash();

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

        $path['fill'] = 'url(#'.$linearGradient['id'].')';
    }

    private function applyFillRadialGradient(SimpleXMLElement $svg, SimpleXMLElement $path, RadialGradient $style)
    {
        $radialGradient = $svg->addChild('radialGradient');

        $radialGradient['gradientTransform'] = $style->matrix->toSvgTransformation();
        $radialGradient['gradientUnits'] = 'userSpaceOnUse';
        $radialGradient['spreadMethod'] = 'pad';
        $radialGradient['id'] = 'gradient-'.$style->hash();

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

        $path['fill'] = 'url(#'.$radialGradient['id'].')';
    }
}
