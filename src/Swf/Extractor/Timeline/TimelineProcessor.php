<?php

declare(strict_types=1);

namespace Arakne\Swf\Extractor\Timeline;

use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DoActionTag;
use Arakne\Swf\Parser\Structure\Tag\EndTag;
use Arakne\Swf\Parser\Structure\Tag\FrameLabelTag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObject2Tag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObject3Tag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObjectTag;
use Arakne\Swf\Parser\Structure\Tag\RemoveObject2Tag;
use Arakne\Swf\Parser\Structure\Tag\RemoveObjectTag;
use Arakne\Swf\Parser\Structure\Tag\ShowFrameTag;

use function assert;
use function ksort;

/**
 * Processor for render the timeline from swf tags
 */
final readonly class TimelineProcessor
{
    /**
     * List of supported tag type ids
     */
    public const array TAG_TYPES = [
        EndTag::TYPE,
        ShowFrameTag::TYPE,
        PlaceObjectTag::TYPE,
        RemoveObjectTag::TYPE,
        DoActionTag::TYPE,
        PlaceObject2Tag::TYPE,
        RemoveObject2Tag::TYPE,
        FrameLabelTag::TYPE,
        PlaceObject3Tag::TYPE,
    ];

    public function __construct(
        private SwfExtractor $extractor,
    ) {}

    /**
     * Process display tags to render frames of the timeline
     *
     * @param iterable<object> $tags
     * @return Timeline
     */
    public function process(iterable $tags): Timeline
    {
        /**
         * @var array<int, FrameObject> $objectsByDepth
         */
        $objectsByDepth = [];

        /**
         * @var list<DoActionTag> $actions
         */
        $actions = [];

        /**
         * @var string|null $frameLabel
         */
        $frameLabel = null;

        /**
         * @var list<Frame> $frames
         */
        $frames = [];

        // Bounds of the sprite
        $xmin = PHP_INT_MAX;
        $ymin = PHP_INT_MAX;
        $xmax = PHP_INT_MIN;
        $ymax = PHP_INT_MIN;

        foreach ($tags as $frameDisplayTag) {
            if ($frameDisplayTag instanceof EndTag) {
                break;
            }

            if ($frameDisplayTag instanceof ShowFrameTag) {
                // Ensure that depths are respected
                ksort($objectsByDepth);

                $frames[] = new Frame(
                    $objectsByDepth ? new Rectangle($xmin, $xmax, $ymin, $ymax) : new Rectangle(0, 0, 0, 0), // Empty frame, use empty bounds
                    $objectsByDepth,
                    $actions,
                    $frameLabel,
                );
                $actions = [];
                $frameLabel = null;
                continue;
            }

            if ($frameDisplayTag instanceof DoActionTag) {
                $actions[] = $frameDisplayTag;
                continue;
            }

            if ($frameDisplayTag instanceof FrameLabelTag) {
                $frameLabel = $frameDisplayTag->label;
                continue;
            }

            if ($frameDisplayTag instanceof RemoveObject2Tag || $frameDisplayTag instanceof RemoveObjectTag) {
                unset($objectsByDepth[$frameDisplayTag->depth]);
                continue;
            }

            if (!$frameDisplayTag instanceof PlaceObject2Tag) {
                // @todo use error collector
                throw new \Exception('Invalid tag ' . get_class($frameDisplayTag));
                //var_dump('Invalid tag ' . get_class($frameDisplayTag));
                //continue;
            }

            if ($frameDisplayTag->characterId !== null) {
                // New character at the given depth
                $objectProperties = $this->placeNewObject($frameDisplayTag);
            } else {
                // Modify the character at the given depth
                $objectProperties = $objectsByDepth[$frameDisplayTag->depth] ?? null;

                if (!$objectProperties) {
                    continue; // @todo error ?
                }

                $objectProperties = $this->modifyObject($frameDisplayTag, $objectProperties);
            }

            $objectsByDepth[$frameDisplayTag->depth] = $objectProperties;
            $currentObjectBounds = $objectProperties->bounds;

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

        $spriteBounds = $objectsByDepth ? new Rectangle($xmin, $xmax, $ymin, $ymax) : new Rectangle(0, 0, 0, 0); // Empty sprite, use empty bounds

        // Use same bounds on all frames
        foreach ($frames as $i => $frame) {
            if ($frame->bounds != $spriteBounds) {
                $frames[$i] = $frame->withBounds($spriteBounds);
            }
        }

        return new Timeline(
            $spriteBounds,
            ...$frames
        );
    }

    /**
     * Handle display of a new object
     *
     * @param PlaceObject2Tag $tag
     * @return FrameObject
     */
    private function placeNewObject(PlaceObject2Tag $tag): FrameObject
    {
        assert($tag->characterId !== null);
        $object = $this->extractor->character($tag->characterId);
        $currentObjectBounds = $object->bounds();

        if ($tag->matrix) {
            // Because the origin shape has already an offset, we need to apply the transformation to the offset
            // And apply the new matrix to the shape
            $newMatrix = $tag->matrix->translate($currentObjectBounds->xmin, $currentObjectBounds->ymin);
            $currentObjectBounds = $currentObjectBounds->transform($tag->matrix);
        } else {
            $newMatrix = new Matrix(
                translateX: $currentObjectBounds->xmin,
                translateY: $currentObjectBounds->ymin,
            );
        }

        if ($tag->colorTransform) {
            $object = $object->transformColors($tag->colorTransform);
        }

        return new FrameObject(
            $tag->characterId,
            $tag->depth,
            $object,
            $currentObjectBounds,
            $newMatrix,
        );
    }

    /**
     * Handle movement/change properties of an already displayed object
     *
     * @param PlaceObject2Tag $tag
     * @param FrameObject $objectProperties
     * @return FrameObject
     */
    private function modifyObject(PlaceObject2Tag $tag, FrameObject $objectProperties): FrameObject
    {

        if ($tag->matrix) {
            $currentObjectBounds = $objectProperties->object->bounds();
            $objectProperties = $objectProperties->with(
                bounds: $currentObjectBounds->transform($tag->matrix),
                matrix: $tag->matrix->translate($currentObjectBounds->xmin, $currentObjectBounds->ymin),
            );
        }

        if ($tag->colorTransform) {
            // Because the color transform is already applied to the previous object, we need to load the original object
            // And apply the color transform to it
            $objectProperties = $objectProperties->with(
                object: $this->extractor
                    ->character($objectProperties->characterId)
                    ->transformColors($tag->colorTransform)
                ,
            );
        }

        return $objectProperties;
    }
}
