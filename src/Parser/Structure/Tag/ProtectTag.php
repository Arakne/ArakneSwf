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

use Arakne\Swf\Parser\SwfReader;

final readonly class ProtectTag
{
    public const int TYPE = 24;

    public function __construct(
        /**
         * Password is an MD5 hash of the password.
         */
        public ?string $password,
    ) {}

    /**
     * Read a Protect tag
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end byte offset of the tag.
     * @return self
     */
    public static function read(SwfReader $reader, int $end): self
    {
        // Password is only present if tag length is not 0
        // It's stored as a null-terminated string
        return new ProtectTag(
            password: $end > $reader->offset ? $reader->readNullTerminatedString() : null,
        );
    }
}
