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

use Arakne\Swf\Parser\Error\ParserOutOfBoundException;
use Arakne\Swf\Parser\SwfReader;

final readonly class DefineVideoStreamTag
{
    public const int TYPE = 60;

    public function __construct(
        public int $characterId,
        public int $numFrames,
        public int $width,
        public int $height,
        public int $deblocking,
        public bool $smoothing,
        public int $codecId,
    ) {}

    /**
     * Read a DefineVideoStream tag from the SWF reader
     *
     * @param SwfReader $reader
     *
     * @return self
     * @throws ParserOutOfBoundException
     */
    public static function read(SwfReader $reader): self
    {
        $characterId = $reader->readUI16();
        $numFrames = $reader->readUI16();
        $width = $reader->readUI16();
        $height = $reader->readUI16();

        $reader->skipBits(4); // Reserved
        $videoFlagsDeblocking = $reader->readUB(3);
        $videoFlagsSmoothing = $reader->readBool();

        $codecId = $reader->readUI8();

        return new DefineVideoStreamTag(
            characterId: $characterId,
            numFrames: $numFrames,
            width: $width,
            height: $height,
            deblocking: $videoFlagsDeblocking,
            smoothing: $videoFlagsSmoothing,
            codecId: $codecId,
        );
    }
}
