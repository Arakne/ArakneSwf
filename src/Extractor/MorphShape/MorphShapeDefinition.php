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
 * Copyright (C) 2025 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Extractor\MorphShape;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Modifier\CharacterModifierInterface;
use Arakne\Swf\Extractor\RatioDrawableInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShape2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShapeTag;
use Override;

/**
 * Define a morph shape character
 *
 * To change the morph "frame", use the withRatio() method,
 * the frame parameter of the draw() method is ignored.
 *
 * @see DefineMorphShapeTag
 * @see DefineMorphShape2Tag
 */
final class MorphShapeDefinition implements RatioDrawableInterface
{
    private ?MorphShape $morphShape = null;

    /**
     * The morph ratio.
     * 0 means the start shape, 65535 means the end shape.
     *
     * @var int<0, 65535>
     */
    private int $ratio = 0;

    public function __construct(
        /**
         * The character ID
         */
        public readonly int $id,
        public readonly DefineMorphShapeTag|DefineMorphShape2Tag $tag,
        private MorphShapeProcessor $processor,
    ) {}

    public function morphShape(): MorphShape
    {
        if ($this->morphShape === null) {
            $this->morphShape = $this->processor->process($this->tag);
            unset($this->processor); // Free memory
        }

        return $this->morphShape;
    }

    #[Override]
    public function withRatio(int $ratio): DrawableInterface
    {
        $self = clone $this;
        $self->ratio = $ratio;

        return $self;
    }

    #[Override]
    public function bounds(): Rectangle
    {
        return $this->morphShape()->bounds($this->ratio);
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        return 1;
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        // @todo cache shape
        $drawer->shape($this->morphShape()->interpolate($this->ratio));

        return $drawer;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): DrawableInterface
    {
        // @todo Implement transformColors() method.
        return $this;
    }

    #[Override]
    public function modify(CharacterModifierInterface $modifier, int $maxDepth = -1): DrawableInterface
    {
        // @todo Implement modify() method.
        return $this;
    }
}
