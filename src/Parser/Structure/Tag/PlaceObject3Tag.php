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

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Error\ParserExceptionInterface;
use Arakne\Swf\Parser\Structure\Record\ClipActions;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Filter\BevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ConvolutionFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\DropShadowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\Filter;
use Arakne\Swf\Parser\Structure\Record\Filter\GlowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientBevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientGlowFilter;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\SwfReader;

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
         * @see PlaceObject2Tag::$clipActions
         */
        public ?ClipActions $clipActions,
    ) {}

    /**
     * Read a PlaceObject3 tag from the SWF reader
     *
     * @param SwfReader $reader
     * @param non-negative-int $swfVersion The SWF version of the file being read
     *
     * @return self
     * @throws ParserExceptionInterface
     */
    public static function read(SwfReader $reader, int $swfVersion): self
    {
        $flags = $reader->readUI8();
        $placeFlagHasClipActions    = ($flags & 0b10000000) !== 0;
        $placeFlagHasClipDepth      = ($flags & 0b01000000) !== 0;
        $placeFlagHasName           = ($flags & 0b00100000) !== 0;
        $placeFlagHasRatio          = ($flags & 0b00010000) !== 0;
        $placeFlagHasColorTransform = ($flags & 0b00001000) !== 0;
        $placeFlagHasMatrix         = ($flags & 0b00000100) !== 0;
        $placeFlagHasCharacter      = ($flags & 0b00000010) !== 0;
        $placeFlagMove              = ($flags & 0b00000001) !== 0;

        $flags = $reader->readUI8();
        // 3 bits reserved, must be 0
        $placeFlagHasImage         = ($flags & 0b00010000) !== 0;
        $placeFlagHasClassName     = ($flags & 0b00001000) !== 0;
        $placeFlagHasCacheAsBitmap = ($flags & 0b00000100) !== 0;
        $placeFlagHasBlendMode     = ($flags & 0b00000010) !== 0;
        $placeFlagHasFilterList    = ($flags & 0b00000001) !== 0;

        return new PlaceObject3Tag(
            move: $placeFlagMove,
            hasImage: $placeFlagHasImage,
            depth: $reader->readUI16(),
            className: $placeFlagHasClassName || ($placeFlagHasImage && $placeFlagHasCharacter) ? $reader->readNullTerminatedString() : null,
            characterId: $placeFlagHasCharacter ? $reader->readUI16() : null,
            matrix: $placeFlagHasMatrix ? Matrix::read($reader) : null,
            colorTransform: $placeFlagHasColorTransform ? ColorTransform::read($reader, true) : null,
            ratio: $placeFlagHasRatio ? $reader->readUI16() : null,
            name: $placeFlagHasName ? $reader->readNullTerminatedString() : null,
            clipDepth: $placeFlagHasClipDepth ? $reader->readUI16() : null,
            surfaceFilterList: $placeFlagHasFilterList ? Filter::readCollection($reader) : null,
            blendMode: $placeFlagHasBlendMode ? $reader->readUI8() : null,
            bitmapCache: $placeFlagHasCacheAsBitmap ? $reader->readUI8() : null,
            clipActions: $placeFlagHasClipActions ? ClipActions::read($reader, $swfVersion) : null,
        );
    }
}
