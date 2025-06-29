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

/**
 * This tag mark the swf as created by rfxswf.
 * It can be ignored.
 *
 * Note: this tag is not documented in the official SWF documentation.
 */
final readonly class ReflexTag
{
    public const int TYPE = 777;

    public function __construct(
        public string $name,
    ) {}

    /**
     * Read a Reflex tag from the SWF reader
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end byte offset of the tag in the SWF file.
     *
     * @return self
     * @throws ParserOutOfBoundException
     */
    public static function read(SwfReader $reader, int $end): self
    {
        return new ReflexTag($reader->readBytesTo($end));
    }
}
