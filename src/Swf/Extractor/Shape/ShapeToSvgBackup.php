<?php

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use SimpleXMLElement;

use function var_dump;

final class ShapeToSvgBackup
{
    public function convert(DefineShapeTag|DefineShape4Tag $tag): string
    {
        $xml = new SimpleXMLElement('<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $width = ($tag->shapeBounds['xmax'] - $tag->shapeBounds['xmin']) / 20;
        $height = ($tag->shapeBounds['ymax'] - $tag->shapeBounds['ymin']) / 20;

        $xml->addAttribute('width', $width.'px');
        $xml->addAttribute('height', $height.'px');

        $g = $xml->addChild('g');
        $xoffset = -$tag->shapeBounds['xmin'] / 20;
        $yoffset = -$tag->shapeBounds['ymin'] / 20;
        $g->addAttribute('transform', "matrix(1.0, 0.0, 0.0, 1, $xoffset, $yoffset)");

        $fillStyles = $tag->shapes['fillStyles'];
        $lineStyles = $tag->shapes['lineStyles'];

        $x = 0;
        $y = 0;
        $fillStyle0Color = null;
        $fillStyle1Color = null;

        foreach ($tag->shapes['shapeRecords'] as $index => $shape) {
            switch ($shape['type']) {
                case 'StyleChangeRecord':
                    $path = $g->addChild('path');

                    if ($shape['stateNewStyles']) {
                        $fillStyles = $shape['fillStyles'];
                        $lineStyles = $shape['lineStyles'];
                    }

                    if (!$shape['stateLineStyle'] || !$shape['lineStyle']) {
                        $path['stroke'] = 'none';
                    } else {
                        if (($color = $lineStyles[$shape['lineStyle'] - 1]['color'] ?? null) !== null) {
                            $path['stroke'] = sprintf('#%02x%02x%02x', $color['red'], $color['green'], $color['blue']);
                            $path['stroke-width'] = $lineStyles[$shape['lineStyle'] - 1]['width'] / 20.0;
                            $path['stroke-linecap'] = 'round';
                            $path['stroke-linejoin'] = 'round';
                        }
                    }

                    if ($shape['stateFillStyle0']) {
                        $fillStyle0Color = $fillStyles[$shape['fillStyle0'] - 1]['color'] ?? null;
                    }

                    if ($shape['stateFillStyle1']) {
                        $fillStyle1Color = $fillStyles[$shape['fillStyle1'] - 1]['color'] ?? null;
                    }

                    if ($fillStyle0Color === null && $fillStyle1Color === null) {
                        $path['fill'] = 'none';
                    } else {
                        // @todo handle 2 fill colors
                        $color = $fillStyle0Color ?? $fillStyle1Color;
                        $path['fill'] = sprintf('#%02x%02x%02x', $color['red'], $color['green'], $color['blue']);
                        $path['fill-rule'] = 'evenodd';
                    }

                    /*if (!empty($shape['stateFillStyle0']) && $shape['fillStyle0']) {
                        if (($color = $fillStyles[$shape['fillStyle0'] - 1]['color'] ?? null) !== null) {
                            $path->addAttribute('fill', sprintf('#%02x%02x%02x', $color['red'], $color['green'], $color['blue']));
                            $path->addAttribute('fill-rule', 'evenodd');
                        }
                    }

                    if (!empty($shape['stateFillStyle1']) && $shape['fillStyle1']) { // @todo gérer les deux fill
                        if (($color = $fillStyles[$shape['fillStyle1'] - 1]['color'] ?? null) !== null) {
                            $path->addAttribute('fill', sprintf('#%02x%02x%02x', $color['red'], $color['green'], $color['blue']));
                            $path->addAttribute('fill-rule', 'evenodd');
                        }
                    }*/

                    if ($shape['stateMoveTo']) {
                        $x = $shape['moveDeltaX'] / 20.0;
                        $y = $shape['moveDeltaY'] / 20.0;

                        $path->addAttribute('d', 'M'.$x.' '.$y);
                    } else {
                        $path['d'] = 'M'.$x.' '.$y;
                    }

                    // @todo compléter

                    break;

                case 'StraightEdgeRecord':
                    if ($shape['generalLineFlag']) {
                        $x += $shape['deltaX'] / 20.0;
                        $y += $shape['deltaY'] / 20.0;
                        $path['d'] .= ' L'.$x.' '.$y;
                    }

                    if (!empty($shape['vertLineFlag'])) {
                        $y += $shape['deltaY'] / 20.0;
                        $path['d'] .= ' V'.$y;
                    }
                    break;

                case 'CurvedEdgeRecord':
                    $path['d'] .= ' Q'.($x + $shape['controlDeltaX'] / 20.0).' '.($y + $shape['controlDeltaY'] / 20.0).' '.($x + $shape['anchorDeltaX'] / 20.0).' '.($y + $shape['anchorDeltaY'] / 20.0);
                    $x += $shape['controlDeltaX'] / 20.0 + $shape['anchorDeltaX'] / 20.0;
                    $y += $shape['controlDeltaY'] / 20.0 + $shape['anchorDeltaY'] / 20.0;
                    break;

                case 'EndShapeRecord':
                    break 2;

                default:
                    throw new \Exception('Unknown shape type: '.$shape['type']);
            }
        }

        return $xml->asXML();
    }
}
