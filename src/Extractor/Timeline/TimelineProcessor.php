<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

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
use Arakne\Swf\Parser\Structure\Tag\SoundStreamHeadTag;

use function assert;
use function ksort;

/**
 * Processor for render the timeline from swf tags
 */
final readonly class TimelineProcessor
{
    /**
     * Maximum bounds size for a sprite
     * This value is arbitrary set to 8192 pixels.
     * Any objects that result in a larger bounds will be added to the display list, but ignored for computing the sprite bounds.
     */
    private const int MAX_BOUNDS = 163_840;

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

        $empty = true;

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

            // Ignore sounds: we only care about display objects
            if ($frameDisplayTag instanceof SoundStreamHeadTag) {
                continue;
            }

            if (!$frameDisplayTag instanceof PlaceObjectTag // @todo test with PlaceObjectTag
                && !$frameDisplayTag instanceof PlaceObject2Tag
                && !$frameDisplayTag instanceof PlaceObject3Tag
            ) {
                // @todo use error collector
                throw new \Exception('Invalid tag ' . get_class($frameDisplayTag));
                //var_dump('Invalid tag ' . get_class($frameDisplayTag));
                //continue;
            }

            $isNewObject = !($frameDisplayTag->move ?? true);

            // New object without characterId is not allowed
            if ($isNewObject && $frameDisplayTag->characterId === null) {
                // @todo: handle PlaceObject3Tag::className if present
                continue;
            }

            // @todo handle PlaceObject3Tag::className if present
            // @todo use move flag instead of check the characterId: the character can be changed even on an existing object
            if ($isNewObject) {
                // New character at the given depth
                $objectProperties = $this->placeNewObject($frameDisplayTag);
            } else {
                // Modify the character at the given depth
                $objectProperties = $objectsByDepth[$frameDisplayTag->depth] ?? null;

                if (!$objectProperties) {
                    continue; // @todo error ?
                }

                assert(!$frameDisplayTag instanceof PlaceObjectTag); // Modify is not possible with PlaceObjectTag
                $objectProperties = $this->modifyObject($frameDisplayTag, $objectProperties);
            }

            $objectsByDepth[$frameDisplayTag->depth] = $objectProperties;
            $currentObjectBounds = $objectProperties->bounds;

            if ($currentObjectBounds->width() > self::MAX_BOUNDS || $currentObjectBounds->height() > self::MAX_BOUNDS) {
                // Do not use this object for computing the sprite bounds
                continue;
            }

            if (
                !$empty && (
                    $currentObjectBounds->xmax - $xmin > self::MAX_BOUNDS
                    || $currentObjectBounds->ymax - $ymin > self::MAX_BOUNDS
                    || $xmax - $currentObjectBounds->xmin > self::MAX_BOUNDS
                    || $ymax - $currentObjectBounds->ymin > self::MAX_BOUNDS
                )
            ) {
                // The placement of this object will result in a sprite that is too large
                // So we ignore it for computing the sprite bounds
                continue;
            }

            $empty = false;

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

        $spriteBounds = !$empty ? new Rectangle($xmin, $xmax, $ymin, $ymax) : new Rectangle(0, 0, 0, 0); // Empty sprite, use empty bounds

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
     * @param PlaceObjectTag|PlaceObject2Tag|PlaceObject3Tag $tag
     * @return FrameObject
     */
    private function placeNewObject(PlaceObjectTag|PlaceObject2Tag|PlaceObject3Tag $tag): FrameObject
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

        return new FrameObject(
            $tag->characterId,
            $tag->depth,
            $object,
            $currentObjectBounds,
            $newMatrix,
            $tag->colorTransform,
            filters: $tag->surfaceFilterList ?? [],
            blendMode: BlendMode::tryFrom($tag->blendMode ?? 1) ?? BlendMode::Normal,
        );
    }

    /**
     * Handle movement/change properties of an already displayed object
     *
     * @param PlaceObject2Tag|PlaceObject3Tag $tag
     * @param FrameObject $objectProperties
     * @return FrameObject
     */
    private function modifyObject(PlaceObject2Tag|PlaceObject3Tag $tag, FrameObject $objectProperties): FrameObject
    {
        if ($tag->characterId !== null) {
            // New object to display, so we need to modify the bounds and matrix according to the new object bounds
            $oldObjectBounds = $objectProperties->object->bounds();
            $newObject = $this->extractor->character($tag->characterId);
            $matrix = $tag->matrix ?? $objectProperties->matrix->translate(-$oldObjectBounds->xmin, -$oldObjectBounds->ymin);
            $newObjectBounds = $newObject->bounds();

            $objectProperties = $objectProperties->with(
                characterId: $tag->characterId,
                object: $newObject,
                bounds: $newObjectBounds->transform($matrix),
                matrix: $matrix->translate($newObjectBounds->xmin, $newObjectBounds->ymin),
            );
        } elseif ($tag->matrix) {
            $currentObjectBounds = $objectProperties->object->bounds();
            $objectProperties = $objectProperties->with(
                bounds: $currentObjectBounds->transform($tag->matrix),
                matrix: $tag->matrix->translate($currentObjectBounds->xmin, $currentObjectBounds->ymin),
            );
        }

        // PlaceObject3Tag properties
        if (isset($tag->blendMode) || isset($tag->surfaceFilterList)) {
            $objectProperties = $objectProperties->with(
                filters: $tag->surfaceFilterList ?? null,
                blendMode: $tag->blendMode !== null ? (BlendMode::tryFrom($tag->blendMode) ?? BlendMode::Normal) : null,
            );
        }

        if ($tag->colorTransform) {
            $objectProperties = $objectProperties->with(colorTransform: $tag->colorTransform);
        }

        return $objectProperties;
    }
}
