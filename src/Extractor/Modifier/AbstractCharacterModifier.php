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
use Arakne\Swf\Extractor\MorphShape\MorphShapeDefinition;
use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\Timeline\Frame;
use Arakne\Swf\Extractor\Timeline\Timeline;
use Override;

/**
 * Provide default implementations for CharacterModifierInterface methods
 * All methods will return the input unmodified.
 */
abstract class AbstractCharacterModifier implements CharacterModifierInterface
{
    #[Override]
    public function applyOnSprite(SpriteDefinition $sprite): SpriteDefinition
    {
        return $sprite;
    }

    #[Override]
    public function applyOnTimeline(Timeline $timeline): Timeline
    {
        return $timeline;
    }

    #[Override]
    public function applyOnFrame(Frame $frame): Frame
    {
        return $frame;
    }

    #[Override]
    public function applyOnShape(ShapeDefinition $shape): ShapeDefinition
    {
        return $shape;
    }

    #[Override]
    public function applyOnMorphShape(MorphShapeDefinition $morphShape): MorphShapeDefinition
    {
        return $morphShape;
    }

    #[Override]
    public function applyOnImage(ImageCharacterInterface $image): ImageCharacterInterface
    {
        return $image;
    }
}
