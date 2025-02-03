<?php

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
            $pathElement['fill'] = (string) ($path->style->fillColor ?? 'none');
            $pathElement['stroke'] = (string) ($path->style->lineColor ?? 'none');

            if ($path->style->fillColor !== null) {
                $pathElement['fill-rule'] = 'evenodd';
            }

            if ($path->style->lineWidth > 0) {
                $pathElement['stroke-width'] = intdiv($path->style->lineWidth, 20);
                $pathElement['stroke-linecap'] = 'round';
                $pathElement['stroke-linejoin'] = 'round';
            }

            $path->draw(new SvgPathDrawer($pathElement));
        }

        return $xml;
    }
}
