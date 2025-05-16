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

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DoActionTag;
use Override;

/**
 * Represent a single from of a timeline
 */
final readonly class Frame implements DrawableInterface
{
    public function __construct(
        /**
         * The display rectangle of the frame
         * It should be the same for all frames of the timeline
         */
        public Rectangle $bounds,

        /**
         * Objects to displayed, ordered by depth
         *
         * @var array<int, FrameObject>
         */
        public array $objects,

        /**
         * Script actions associated with this frame
         *
         * @var list<DoActionTag>
         */
        public array $actions,

        /**
         * The frame label.
         * Can be use by the action go to label to jump to this frame.
         */
        public ?string $label,
    ) {}

    #[Override]
    public function bounds(): Rectangle
    {
        return $this->bounds;
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        if (!$recursive) {
            return 1;
        }

        $count = 1;

        foreach ($this->objects as $object) {
            $objectFramesCount = $object->object->framesCount(true);

            if ($objectFramesCount > $count) {
                $count = $objectFramesCount;
            }
        }

        return $count;
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        $drawer->area($this->bounds);

        foreach ($this->objects as $object) {
            $drawer->include(
                $object->transformedObject(),
                $object->matrix,
                $frame,
                $object->filters,
                $object->blendMode,
            );
        }

        return $drawer;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): self
    {
        $objects = [];

        foreach ($this->objects as $object) {
            $objects[] = $object->transformColors($colorTransform);
        }

        return new self(
            $this->bounds,
            $objects,
            $this->actions,
            $this->label
        );
    }

    /**
     * Modify the bounds of the frame.
     * This method allow to keep the same bounds on all frames on the sprite.
     *
     * @param Rectangle $newBounds
     * @return self
     */
    public function withBounds(Rectangle $newBounds): self
    {
        return new self(
            $newBounds,
            $this->objects,
            $this->actions,
            $this->label
        );
    }
}
