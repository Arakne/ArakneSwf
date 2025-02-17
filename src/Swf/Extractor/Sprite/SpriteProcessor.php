<?php

namespace Arakne\Swf\Extractor\Sprite;

use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObject2Tag;

use Arakne\Swf\Parser\Structure\Tag\ShowFrameTag;

use function get_class;
use function ksort;
use function method_exists;
use function var_dump;

final readonly class SpriteProcessor
{
    public function __construct(
        private SwfExtractor $extractor,
    ) {}

    public function process(DefineSpriteTag $tag): Sprite
    {
        /**
         * @var array<int, SpriteObject> $objectsByDepth
         */
        $objectsByDepth = [];

        // Bounds of the sprite
        $xmin = PHP_INT_MAX;
        $ymin = PHP_INT_MAX;
        $xmax = PHP_INT_MIN;
        $ymax = PHP_INT_MIN;

        foreach ($tag->tags as $placeObjectTag) {
            if ($placeObjectTag instanceof ShowFrameTag) {
                break;
            }

            if (!$placeObjectTag instanceof PlaceObject2Tag) {
                //throw new Exception('Invalid tag ' . get_class($placeObjectTag));
                continue;
            }

            // @todo handle move
            if ($placeObjectTag->characterId === null) {
                continue;
            }

            $object = $this->extractor->character($placeObjectTag->characterId);
            $currentObjectBounds = $object->bounds();

            if ($placeObjectTag->matrix) {
                // Because the origin shape has already an offset, we need to apply the transformation to the offset
                // And apply the new matrix to the shape
                $newMatrix = $placeObjectTag->matrix->translate($currentObjectBounds->xmin, $currentObjectBounds->ymin);
                $currentObjectBounds = $currentObjectBounds->transform($placeObjectTag->matrix);
            } else {
                $newMatrix = new Matrix(
                    translateX: $currentObjectBounds->xmin,
                    translateY: $currentObjectBounds->ymin,
                );
            }

            if ($placeObjectTag->colorTransform) {
                $object = $object->transformColors($placeObjectTag->colorTransform);
            }

            $objectsByDepth[$placeObjectTag->depth] = new SpriteObject(
                $placeObjectTag->depth,
                $object,
                $currentObjectBounds,
                $newMatrix,
            );

            if ($currentObjectBounds->xmax > $xmax) {
                $xmax = $currentObjectBounds->xmax;
            }

            if ($currentObjectBounds->xmin < $xmin) {
                $xmin = $currentObjectBounds->xmin;
            }

            if ($currentObjectBounds->ymax > $ymax) {
                $ymax = $currentObjectBounds->ymax;
            }

            if ($currentObjectBounds->ymin < $ymin) {
                $ymin = $currentObjectBounds->ymin;
            }
        }

        // Ensure that depths are respected
        ksort($objectsByDepth);

        return new Sprite(
            $objectsByDepth ? new Rectangle($xmin, $xmax, $ymin, $ymax) : new Rectangle(0, 0, 0, 0), // Empty sprite, use empty bounds
            ...$objectsByDepth
        );
    }
}
