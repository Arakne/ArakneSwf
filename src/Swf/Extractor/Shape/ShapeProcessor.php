<?php

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Parser\Structure\Record\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\StyleChangeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use InvalidArgumentException;

/**
 * Process define shape action tags to create shape objects
 */
final class ShapeProcessor
{
    public function process(DefineShapeTag|DefineShape4Tag $tag): Shape
    {
        return new Shape(
            width: $tag->shapeBounds->width(),
            height: $tag->shapeBounds->height(),
            xOffset: -$tag->shapeBounds->xmin,
            yOffset: -$tag->shapeBounds->ymin,
            paths: $this->processPaths($tag),
        );
    }

    /**
     * @return list<Path>
     */
    private function processPaths(DefineShapeTag|DefineShape4Tag $tag): array
    {
        $fillStyles = $tag->shapes->fillStyles;
        $lineStyles = $tag->shapes->lineStyles;

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
        $edges = [];

        foreach ($tag->shapes->shapeRecords as $shape) {
            switch (true) {
                case $shape instanceof StyleChangeRecord:
                    $builder->merge(...$edges);
                    $edges = [];

                    if ($shape->stateNewStyles) {
                        // Reset styles to ensure that we don't use old styles
                        $builder->close();

                        $fillStyles = $shape->fillStyles;
                        $lineStyles = $shape->lineStyles;
                    }

                    if ($shape->stateLineStyle) {
                        $style = $lineStyles[$shape->lineStyle - 1] ?? null;
                        if ($style !== null) {
                            $lineStyle = new PathStyle(
                                lineColor: $style->color,
                                lineWidth: $style->width,
                            );
                        } else {
                            $lineStyle = null;
                        }
                    }

                    if ($shape->stateFillStyle0) {
                        $style = $fillStyles[$shape->fillStyle0 - 1] ?? null;
                        if ($style !== null) {
                            $fillStyle0 = new PathStyle(fillColor: $style->color);
                        } else {
                            $fillStyle0 = null;
                        }
                    }

                    if ($shape->stateFillStyle1) {
                        $style = $fillStyles[$shape->fillStyle1 - 1] ?? null;
                        if ($style !== null) {
                            $fillStyle1 = new PathStyle(fillColor: $style->color);
                        } else {
                            $fillStyle1 = null;
                        }
                    }

                    $builder->setActiveStyles($fillStyle0, $fillStyle1, $lineStyle);

                    if ($shape->stateMoveTo) {
                        $x = $shape->moveDeltaX;
                        $y = $shape->moveDeltaY;
                    }
                    break;

                case $shape instanceof StraightEdgeRecord:
                    $toX = $x + $shape->deltaX;
                    $toY = $y + $shape->deltaY;

                    $edges[] = new StraightEdge($x, $y, $toX, $toY);

                    $x = $toX;
                    $y = $toY;
                    break;

                case $shape instanceof CurvedEdgeRecord:
                    $fromX = $x;
                    $fromY = $y;
                    $controlX = $x + $shape->controlDeltaX;
                    $controlY = $y + $shape->controlDeltaY;
                    $toX = $x + $shape->controlDeltaX + $shape->anchorDeltaX;
                    $toY = $y + $shape->controlDeltaY + $shape->anchorDeltaY;

                    $edges[] = new CurvedEdge($fromX, $fromY, $controlX, $controlY, $toX, $toY);

                    $x = $toX;
                    $y = $toY;
                    break;

                case $shape instanceof EndShapeRecord:
                    $builder->merge(...$edges);
                    return $builder->export();

                default:
                    throw new InvalidArgumentException('Unknown shape type: '.$shape::class);
            }
        }

        return $builder->export();
    }
}
