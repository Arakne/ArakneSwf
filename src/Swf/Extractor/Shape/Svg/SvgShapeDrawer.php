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

use Arakne\Swf\Extractor\Shape\Shape;
use SimpleXMLElement;

use function intdiv;
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

        $xml->addAttribute('width', intdiv($shape->width, 20).'px');
        $xml->addAttribute('height', intdiv($shape->height, 20).'px');

        $g = $xml->addChild('g');
        $g->addAttribute('transform', sprintf(
            'matrix(1.0, 0.0, 0.0, 1, %h, %h)',
            $shape->xOffset / 20,
            $shape->yOffset / 20,
        ));

        foreach ($shape->paths as $path) {
            $pathElement = $g->addChild('path');
            $pathElement['fill'] = $path->style->fillColor?->hex() ?? 'none';
            $pathElement['stroke'] = $path->style->lineColor?->hex() ?? 'none';

            if ($path->style->fillColor !== null) {
                $pathElement['fill-rule'] = 'evenodd';
            }

            if ($path->style->fillColor?->hasTransparency() === true) {
                $pathElement['fill-opacity'] = $path->style->fillColor->opacity();
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
}
