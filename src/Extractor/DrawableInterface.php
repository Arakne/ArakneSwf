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

use Arakne\Swf\Error\SwfExceptionInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Modifier\CharacterModifierInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

/**
 * Base type for SWF characters that can be drawn
 */
interface DrawableInterface
{
    /**
     * Size and offset of the character
     *
     * @throws SwfExceptionInterface
     */
    public function bounds(): Rectangle;

    /**
     * Get the number of frames contained in the character
     *
     * @param bool $recursive If true, will count the frames of all children recursively
     *
     * @return positive-int
     * @throws SwfExceptionInterface
     */
    public function framesCount(bool $recursive = false): int;

    /**
     * Draw the current character to the canvas
     *
     * @param D $drawer The drawer to use
     * @param non-negative-int $frame The frame to draw. Must be greater than or equal to 0. If this value is greater than the number of frames in the character, the last frame will be used.
     * @return D The passed drawer
     *
     * @template D as DrawerInterface
     * @throws SwfExceptionInterface
     */
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface;

    /**
     * Transform the colors of the character
     * In case of composite characters, the transformation should be applied to all children recursively
     *
     * The current instance of the character should not be modified, a new instance should be returned
     *
     * @param ColorTransform $colorTransform
     *
     * @return self The transformed character
     * @throws SwfExceptionInterface
     */
    public function transformColors(ColorTransform $colorTransform): self;

    /**
     * Modify the current character and its children recursively using the given modifier.
     * The current instance of the character will not be modified, a new instance should be returned.
     *
     * If children elements are modified, the current character will be updated to fully use the modified children (e.g. update bounds, etc.).
     * If no modification is applied, the current instance may be returned.
     *
     * @param CharacterModifierInterface $modifier
     * @param int $maxDepth Maximum depth to modify. Use -1 for infinite depth. Use 0 to modify only the current character.
     *
     * @return self The modified character
     */
    public function modify(CharacterModifierInterface $modifier, int $maxDepth = -1): self;
}
