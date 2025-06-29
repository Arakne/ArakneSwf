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

final readonly class FileAttributesTag
{
    public const int TYPE = 69;

    public function __construct(
        public bool $useDirectBlit,
        public bool $useGpu,
        public bool $hasMetadata,
        public bool $actionScript3,
        public bool $useNetwork,
    ) {}

    /**
     * Read a FileAttributes tag from the SWF reader
     *
     * @param SwfReader $reader
     *
     * @return self
     * @throws ParserOutOfBoundException
     */
    public static function read(SwfReader $reader): self
    {
        $flags = $reader->readUI8();
        // 1 bit reserved, must be 0
        $useDirectBlit = ($flags & 0b01000000) !== 0;
        $useGpu        = ($flags & 0b00100000) !== 0;
        $hasMetadata   = ($flags & 0b00010000) !== 0;
        $actionScript3 = ($flags & 0b00001000) !== 0;
        // 2 bits reserved, must be 0
        $useNetwork    = ($flags & 0b00000001) !== 0;

        $reader->skipBytes(3); // Reserved, must be 0

        return new self(
            useDirectBlit: $useDirectBlit,
            useGpu: $useGpu,
            hasMetadata: $hasMetadata,
            actionScript3: $actionScript3,
            useNetwork: $useNetwork,
        );
    }
}
