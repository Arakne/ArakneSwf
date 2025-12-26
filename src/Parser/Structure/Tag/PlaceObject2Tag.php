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
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\SwfReader;

final readonly class PlaceObject2Tag
{
    public const int TYPE = 26;

    public function __construct(
        public bool $move,
        public int $depth,
        public ?int $characterId,
        public ?Matrix $matrix,
        public ?ColorTransform $colorTransform,

        /**
         * @var int<0, 65535>|null
         */
        public ?int $ratio,
        public ?string $name,
        public ?int $clipDepth,
        public ?ClipActions $clipActions,
    ) {}

    /**
     * Read a PlaceObject2 tag
     *
     * @param SwfReader $reader
     * @param non-negative-int $swfVersion The SWF version of the file being read.
     *
     * @return self
     * @throws ParserExceptionInterface
     */
    public static function read(SwfReader $reader, int $swfVersion): self
    {
        $flags = $reader->readUI8();
        $hasClipActions    = ($flags & 0b10000000) !== 0;
        $hasClipDepth      = ($flags & 0b01000000) !== 0;
        $hasName           = ($flags & 0b00100000) !== 0;
        $hasRatio          = ($flags & 0b00010000) !== 0;
        $hasColorTransform = ($flags & 0b00001000) !== 0;
        $hasMatrix         = ($flags & 0b00000100) !== 0;
        $hasCharacter      = ($flags & 0b00000010) !== 0;
        $move              = ($flags & 0b00000001) !== 0;

        return new self(
            move: $move,
            depth: $reader->readUI16(),
            characterId: $hasCharacter ? $reader->readUI16() : null,
            matrix: $hasMatrix ? Matrix::read($reader) : null,
            colorTransform: $hasColorTransform ? ColorTransform::read($reader, withAlpha: true) : null,
            ratio: $hasRatio ? $reader->readUI16() : null,
            name: $hasName ? $reader->readNullTerminatedString() : null,
            clipDepth: $hasClipDepth ? $reader->readUI16() : null,
            clipActions: $hasClipActions ? ClipActions::read($reader, $swfVersion) : null,
        );
    }
}
