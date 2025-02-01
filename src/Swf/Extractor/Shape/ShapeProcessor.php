<?php

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;

use function sprintf;

/**
 * Process define shape action tags to create shape objects
 */
final class ShapeProcessor
{
    public function process(DefineShapeTag|DefineShape4Tag $tag): Shape
    {
        $fillStyles = $tag->shapes['fillStyles'];
        $lineStyles = $tag->shapes['lineStyles'];

        $x = 0;
        $y = 0;

        /**
         * @var PathStyle|null $fillStyle0
         * @var PathStyle|null $fillStyle1
         * @var PathStyle|null $lineStyle
         */
        $fillStyle0 = null;
        $fillStyle1 = null;
        $lineStyle = null;

        $builder = new PathsBuilder();
        $currentPath = new Path();

        foreach ($tag->shapes['shapeRecords'] as $shape) {
            switch ($shape['type']) {
                case 'StyleChangeRecord':
                    $builder->merge($currentPath);
                    $currentPath = new Path();

                    if ($shape['stateNewStyles']) {
                        // Reset styles to ensure that we don't use old styles
                        $builder->close();

                        $fillStyles = $shape['fillStyles'];
                        $lineStyles = $shape['lineStyles'];
                    }

                    if ($shape['stateLineStyle']) {
                        $colorArr = $lineStyles[$shape['lineStyle'] - 1]['color'] ?? null;
                        if ($colorArr !== null) {
                            $lineStyle = new PathStyle(
                                lineColor: sprintf('#%02x%02x%02x', $colorArr['red'], $colorArr['green'], $colorArr['blue']),
                                lineWidth: $lineStyles[$shape['lineStyle'] - 1]['width'] ?? 0,
                            );
                        } else {
                            $lineStyle = null;
                        }
                    }

                    if ($shape['stateFillStyle0']) {
                        $colorArr = $fillStyles[$shape['fillStyle0'] - 1]['color'] ?? null;
                        if ($colorArr !== null) {
                            $fillStyle0 = new PathStyle(fillColor: sprintf('#%02x%02x%02x', $colorArr['red'], $colorArr['green'], $colorArr['blue']));
                        } else {
                            $fillStyle0 = null;
                        }
                    }

                    if ($shape['stateFillStyle1']) {
                        $colorArr = $fillStyles[$shape['fillStyle1'] - 1]['color'] ?? null;
                        if ($colorArr !== null) {
                            $fillStyle1 = new PathStyle(fillColor: sprintf('#%02x%02x%02x', $colorArr['red'], $colorArr['green'], $colorArr['blue']));
                        } else {
                            $fillStyle1 = null;
                        }
                    }

                    $builder->setActiveStyles($fillStyle0, $fillStyle1, $lineStyle);

                    if ($shape['stateMoveTo']) {
                        $x = $shape['moveDeltaX'];
                        $y = $shape['moveDeltaY'];
                    }

                    // @todo compléter

                    break;

                case 'StraightEdgeRecord':
                    if ($shape['generalLineFlag']) {
                        $toX = $x + $shape['deltaX'];
                        $toY = $y + $shape['deltaY'];

                        $currentPath->edges[] = new StraightEdge($x, $y, $toX, $toY);

                        $x = $toX;
                        $y = $toY;
                    } elseif (!empty($shape['vertLineFlag'])) {
                        $toY = $y + $shape['deltaY'];

                        $currentPath->edges[] = new StraightEdge($x, $y, $x, $toY);

                        $y = $toY;
                    } else {
                        $toX = $x + $shape['deltaX'];

                        $currentPath->edges[] = new StraightEdge($x, $y, $toX, $y);

                        $x = $toX;
                    }
                    break;

                case 'CurvedEdgeRecord':
                    $fromX = $x;
                    $fromY = $y;
                    $controlX = $x + $shape['controlDeltaX'];
                    $controlY = $y + $shape['controlDeltaY'];
                    $toX = $x + $shape['controlDeltaX'] + $shape['anchorDeltaX'];
                    $toY = $y + $shape['controlDeltaY'] + $shape['anchorDeltaY'];

                    $currentPath->edges[] = new CurvedEdge($fromX, $fromY, $controlX, $controlY, $toX, $toY);

                    $x = $toX;
                    $y = $toY;
                    break;

                case 'EndShapeRecord':
                    $builder->merge($currentPath);
                    break 2;

                default:
                    throw new \Exception('Unknown shape type: '.$shape['type']);
            }
        }

        return new Shape(
            width: ($tag->shapeBounds['xmax'] - $tag->shapeBounds['xmin']),
            height: ($tag->shapeBounds['ymax'] - $tag->shapeBounds['ymin']),
            xOffset: -$tag->shapeBounds['xmin'],
            yOffset: -$tag->shapeBounds['ymin'],
            paths: $builder->export(),
        );
    }
}
