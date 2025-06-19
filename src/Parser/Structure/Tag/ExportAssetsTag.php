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

use function assert;
use function count;

final readonly class ExportAssetsTag
{
    public const int ID = 56;

    public function __construct(
        /** @var list<int> */
        public array $tags,

        /** @var list<string> */
        public array $names,
    ) {
        // @todo use associative array instead of two lists
        assert(count($this->tags) === count($this->names));
    }

    /**
     * Read an ExportAssetsTag from the SWF reader
     *
     * @param SwfReader $reader
     * @return self
     */
    public static function read(SwfReader $reader): self
    {
        $tags = [];
        $names = [];
        $count = $reader->readUI16();

        for ($i = 0; $i < $count; $i++) {
            $tags[] = $reader->readUI16();
            $names[] = $reader->readNullTerminatedString();
        }

        return new ExportAssetsTag(
            tags: $tags,
            names: $names,
        );
    }
}
