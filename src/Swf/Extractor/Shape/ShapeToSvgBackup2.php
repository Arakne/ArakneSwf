<?php

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use SimpleXMLElement;

use function var_dump;

final class ShapeToSvgBackup2
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
        $lineStyleColor = null; // @todo store also width

        $currentPaths = [];

        foreach ($tag->shapes['shapeRecords'] as $index => $shape) {
            switch ($shape['type']) {
                case 'StyleChangeRecord':
                    $currentPaths = [];

                    if ($shape['stateNewStyles']) {
                        $fillStyles = $shape['fillStyles'];
                        $lineStyles = $shape['lineStyles'];
                    }

                    if ($shape['stateLineStyle']) {
                        $lineStyleColor = $lineStyles[$shape['lineStyle'] - 1]['color'] ?? null;
                    }

                    if ($shape['stateFillStyle0']) {
                        $fillStyle0Color = $fillStyles[$shape['fillStyle0'] - 1]['color'] ?? null;
                    }

                    if ($shape['stateFillStyle1']) {
                        $fillStyle1Color = $fillStyles[$shape['fillStyle1'] - 1]['color'] ?? null;
                    }

                    if ($lineStyleColor) {
                        $path = $g->addChild('path');
                        $path['fill'] = 'none';
                        $path['stroke'] = sprintf('#%02x%02x%02x', $lineStyleColor['red'], $lineStyleColor['green'], $lineStyleColor['blue']);
                        $path['stroke-width'] = $lineStyles[$shape['lineStyle'] - 1]['width'] / 20.0;
                        $path['stroke-linecap'] = 'round';
                        $path['stroke-linejoin'] = 'round';
                        $currentPaths[] = $path;
                    }

                    if ($fillStyle0Color !== null) {
                        $otherPath = $g->addChild('path');
                        $otherPath['fill'] = sprintf('#%02x%02x%02x', $fillStyle0Color['red'], $fillStyle0Color['green'], $fillStyle0Color['blue']);
                        $otherPath['fill-rule'] = 'evenodd';
                        $otherPath['stroke'] = 'none';
                        $currentPaths[] = $otherPath;
                    }

                    if ($fillStyle1Color !== null) {
                        $otherPath = $g->addChild('path');
                        $otherPath['fill'] = sprintf('#%02x%02x%02x', $fillStyle1Color['red'], $fillStyle1Color['green'], $fillStyle1Color['blue']);
                        $otherPath['fill-rule'] = 'evenodd';
                        $otherPath['stroke'] = 'none';
                        $currentPaths[] = $otherPath;
                    }

                    foreach ($currentPaths as $path) {
                        if ($shape['stateMoveTo']) {
                            $x = $shape['moveDeltaX'] / 20.0;
                            $y = $shape['moveDeltaY'] / 20.0;

                            $path->addAttribute('d', 'M' . $x . ' ' . $y);
                        } else {
                            $path['d'] = 'M' . $x . ' ' . $y;
                        }
                    }

                    // @todo compléter

                    break;

                case 'StraightEdgeRecord':
                    if ($shape['generalLineFlag']) {
                        $x += $shape['deltaX'] / 20.0;
                        $y += $shape['deltaY'] / 20.0;

                        foreach ($currentPaths as $path) {
                            $path['d'] .= ' L' . $x . ' ' . $y;
                        }
                    }

                    if (!empty($shape['vertLineFlag'])) {
                        $y += $shape['deltaY'] / 20.0;

                        foreach ($currentPaths as $path) {
                            $path['d'] .= ' V' . $y;
                        }
                    }
                    break;

                case 'CurvedEdgeRecord':
                    foreach ($currentPaths as $path) {
                        $path['d'] .= ' Q' . ($x + $shape['controlDeltaX'] / 20.0) . ' ' . ($y + $shape['controlDeltaY'] / 20.0) . ' ' . ($x + $shape['anchorDeltaX'] / 20.0) . ' ' . ($y + $shape['anchorDeltaY'] / 20.0);
                    }

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
