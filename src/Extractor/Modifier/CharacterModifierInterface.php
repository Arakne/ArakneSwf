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

namespace Arakne\Swf\Extractor\Modifier;

use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\Timeline\Frame;
use Arakne\Swf\Extractor\Timeline\Timeline;

/**
 * Base type for applying modifications to character definitions
 *
 * Prefer use {@see AbstractCharacterModifier}, which provides default implementations: new methods may be added in the future,
 * breaking existing implementations of this interface.
 */
interface CharacterModifierInterface
{
    public function applyOnSprite(SpriteDefinition $sprite): SpriteDefinition;
    public function applyOnTimeline(Timeline $timeline): Timeline;
    public function applyOnFrame(Frame $frame): Frame;
    public function applyOnShape(ShapeDefinition $shape): ShapeDefinition;
    public function applyOnImage(ImageCharacterInterface $image): ImageCharacterInterface;
}
