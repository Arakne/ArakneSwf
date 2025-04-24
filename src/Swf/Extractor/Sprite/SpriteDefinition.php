<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Arakne-Swf: derived from SWF.php
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Extractor\Sprite;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Override;

use function count;
use function min;

/**
 * Store an SWF sprite character
 *
 * @see DefineSpriteTag
 */
final class SpriteDefinition implements DrawableInterface
{
    private ?Sprite $sprite = null;

    public function __construct(
        private SpriteProcessor $processor,

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
     * Get the sprite object
     * The sprite is processed only once and cached
     */
    public function sprite(): Sprite
    {
        if (!$this->sprite) {
            $this->sprite = $this->processor->process($this->tag);
            unset($this->processor); // Remove the processor to remove cyclic reference
        }

        return $this->sprite;
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        $count = count($this->sprite()->frames);

        if (!$recursive) {
            return $count;
        }

        foreach ($this->sprite()->frames as $index => $frame) {
            $frameCount = $frame->framesCount(true) + $index;

            if ($frameCount > $count) {
                $count = $frameCount;
            }
        }

        return $count;
    }

    #[Override]
    public function bounds(): Rectangle
    {
        return $this->sprite()->bounds;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        $sprite = $this->sprite()->transformColors($colorTransform);

        $self = clone $this;
        $self->sprite = $sprite;

        return $self;
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        $frames = $this->sprite()->frames;
        $currentFrame = min($frame, count($frames) - 1);

        return $frames[$currentFrame]->draw($drawer, $frame);
    }

    /**
     * Convert the sprite to SVG string
     */
    public function toSvg(int $frame = 0): string
    {
        return $this->draw(new SvgCanvas($this->bounds()), $frame)->render();
    }
}
