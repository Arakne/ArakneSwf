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

namespace Arakne\Swf\Extractor\Sprite;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Timeline\TimelineProcessor;
use Arakne\Swf\Extractor\Timeline\Timeline;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Override;

/**
 * Store an SWF sprite character
 *
 * @see DefineSpriteTag
 */
final class SpriteDefinition implements DrawableInterface
{
    private ?Timeline $timeline = null;

    public function __construct(
        private TimelineProcessor $processor,

        /**
         * The character ID of the sprite
         *
         * @see SwfTagPosition::$id
         */
        public readonly int $id,

        /**
         * The raw SWF tag
         */
        public readonly DefineSpriteTag $tag,
    ) {}

    /**
     * Get the timeline object
     * The timeline is processed only once and cached
     */
    public function timeline(): Timeline
    {
        if (!$this->timeline) {
            $this->timeline = $this->processor->process($this->tag->tags);
            unset($this->processor); // Remove the processor to remove cyclic reference
        }

        return $this->timeline;
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        return $this->timeline()->framesCount($recursive);
    }

    #[Override]
    public function bounds(): Rectangle
    {
        return $this->timeline()->bounds;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        $sprite = $this->timeline()->transformColors($colorTransform);

        $self = clone $this;
        $self->timeline = $sprite;

        return $self;
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        return $this->timeline()->draw($drawer, $frame);
    }

    /**
     * Convert the sprite to SVG string
     *
     * @param non-negative-int $frame The frame to render
     */
    public function toSvg(int $frame = 0): string
    {
        return $this->timeline()->toSvg($frame);
    }
}
