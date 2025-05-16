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

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Filter\BevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ConvolutionFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\DropShadowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GlowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientBevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientGlowFilter;
use Arakne\Swf\Parser\Structure\Record\Matrix;

final readonly class PlaceObject3Tag
{
    public const int TYPE = 70;

    public function __construct(
        /**
         * @see PlaceObject2Tag::$move
         */
        public bool $move,

        /**
         * Introduced in PlaceObject3
         */
        public bool $hasImage,

        /**
         * @see PlaceObjectTag::$depth
         */
        public int $depth,

        /**
         * Introduced in PlaceObject3
         */
        public ?string $className,

        /**
         * @see PlaceObjectTag::$characterId
         */
        public ?int $characterId,

        /**
         * @see PlaceObjectTag::$matrix
         */
        public ?Matrix $matrix,

        /**
         * @see PlaceObjectTag::$colorTransform
         */
        public ?ColorTransform $colorTransform,

        /**
         * @see PlaceObject2Tag::$ratio
         */
        public ?int $ratio,

        /**
         * @see PlaceObject2Tag::$ratio
         */
        public ?string $name,

        /**
         * @see PlaceObject2Tag::$clipDepth
         */
        public ?int $clipDepth,

        /**
         * Introduced in PlaceObject3
         *
         * @var list<DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter>|null
         */
        public ?array $surfaceFilterList,

        /**
         * Introduced in PlaceObject3
         */
        public ?int $blendMode,

        /**
         * Introduced in PlaceObject3
         */
        public ?int $bitmapCache,

        /**
         * @var array<string, mixed>|null
         * @see PlaceObject2Tag::$clipActions
         */
        public ?array $clipActions,
    ) {}
}
