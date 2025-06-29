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

use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Error\ParserOutOfBoundException;
use Arakne\Swf\Parser\SwfReader;

final readonly class EnableDebuggerTag
{
    public const int TYPE_V1 = 58;
    public const int TYPE_V2 = 64;

    public function __construct(
        /**
         * The version of the EnableDebugger tag.
         *
         * This is either 1 or 2, depending on the tag type.
         *
         * @var int<1, 2>
         */
        public int $version,
        public string $password,
    ) {}

    /**
     * Read an EnableDebugger or EnableDebugger2 tag from the SWF reader
     *
     * @param SwfReader $reader
     * @param int<1, 2> $version The version of the EnableDebugger tag
     *
     * @return self
     *
     * @throws ParserOutOfBoundException
     * @throws ParserInvalidDataException
     */
    public static function read(SwfReader $reader, int $version): self
    {
        if ($version === 2) {
            $reader->skipBytes(2); // Reserved, must be 0
        }

        return new EnableDebuggerTag(
            version: $version,
            password: $reader->readNullTerminatedString(),
        );
    }
}
