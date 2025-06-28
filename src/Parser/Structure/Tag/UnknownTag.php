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

use Arakne\Swf\Parser\Error\Errors;
use Arakne\Swf\Parser\Error\UnknownTagException;
use Arakne\Swf\Parser\SwfReader;

/**
 * Unknown tag.
 * Can be used to represent a tag that is not yet implemented, an error, a custom tag,
 * or obfuscation mechanism.
 */
final readonly class UnknownTag
{
    public function __construct(
        public int $code,
        public string $data,
    ) {}

    /**
     * @param SwfReader $reader
     * @param non-negative-int $code
     * @param non-negative-int $end
     *
     * @return self
     */
    public static function create(SwfReader $reader, int $code, int $end): self
    {
        if ($reader->errors & Errors::UNKNOWN_TAG) {
            throw new UnknownTagException($code, $reader->offset);
        }

        return new UnknownTag(
            code: $code,
            data: $reader->readBytesTo($end),
        );
    }
}
