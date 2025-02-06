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

use Arakne\Swf\Extractor\Shape\Svg\SvgCanvas;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;

final class SpriteDefinition
{
    public private(set) Sprite $sprite {
        get => $this->sprite ??= $this->processor->process($this->tag);
    }

    public Rectangle $bounds {
        get => $this->sprite->bounds;
    }

    public function __construct(
        private readonly SpriteProcessor $processor,

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

    public function transformColors(array $colorTransform): self
    {
        $sprite = $this->sprite->transformColors($colorTransform);

        $self = clone $this;
        $self->sprite = $sprite;

        return $self;
    }

    public function draw(SvgCanvas $canvas): SvgCanvas
    {
        $canvas->bounds($this->bounds);

        foreach ($this->sprite->objects as $object) {
            $canvas->include($object->object, $object->matrix);
        }

        return $canvas;
    }

    /**
     * Convert the sprite to SVG string
     */
    public function toSvg(): string
    {
        return $this->draw(new SvgCanvas($this->bounds))->toXml();
    }
}
