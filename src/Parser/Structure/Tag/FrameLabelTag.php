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

final readonly class FrameLabelTag
{
    public const int TYPE = 43;

    public function __construct(
        public string $label,
        public bool $namedAnchor = false,
    ) {}

    /**
     * Read a FrameLabel tag
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end byte offset of the tag.
     *
     * @return self
     *
     * @throws ParserOutOfBoundException
     * @throws ParserInvalidDataException
     */
    public static function read(SwfReader $reader, int $end): self
    {
        // Parse null-terminated string
        $label = $reader->readNullTerminatedString();

        // Since SWF 6, the flag namedAnchor is present to create a named anchor
        // So we need to check if there is still data to read, and if so, read the flag
        $hasMoreData = $reader->offset < $end;

        return new FrameLabelTag(
            $label,
            $hasMoreData && $reader->readUI8() === 1,
        );
    }
}
