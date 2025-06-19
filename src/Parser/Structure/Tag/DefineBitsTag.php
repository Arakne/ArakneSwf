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

final readonly class DefineBitsTag
{
    public const int TYPE = 6;

    public function __construct(
        public int $characterId,
        public string $imageData,
    ) {}

    /**
     * Read a DefineBitsTag from the reader.
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end byte offset of the tag in the SWF file
     *
     * @return self
     */
    public static function read(SwfReader $reader, int $end): self
    {
        $characterId = $reader->readUI16();
        $len = $end - $reader->offset;
        assert($len >= 0);

        return new DefineBitsTag(
            characterId: $characterId,
            imageData: $reader->readBytes($len), // @todo read until method
        );
    }
}
