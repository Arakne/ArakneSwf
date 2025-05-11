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

namespace Arakne\Swf\Extractor;

use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Override;

/**
 * Type for nonexistent character
 */
final readonly class MissingCharacter implements DrawableInterface
{
    public function __construct(
        /**
         * The character ID of the requested character
         *
         * @see SwfTagPosition::$id
         */
        public int $id,
    ) {}

    #[Override]
    public function bounds(): Rectangle
    {
        return new Rectangle(0, 0, 0, 0);
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        return 1;
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        return $drawer;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        return $this;
    }
}
