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

final readonly class ImportAssetsTag
{
    public const int TYPE_V1 = 57;
    public const int TYPE_V2 = 71;

    public function __construct(
        /**
         * The version of the ImportAssets tag.
         *
         * This is either 1 or 2, depending on the tag type.
         *
         * @var int<1, 2>
         */
        public int $version,

        public string $url,

        /**
         * Map of character IDs to their names.
         *
         * The value corresponds to the exported name from the other SWF,
         * and the key is the character ID in the current SWF (will be defined into the dictionary).
         *
         * @var array<int, string>
         */
        public array $characters,
    ) {}

    /**
     * Read an ImportAssets or ImportAssets2 tag from the SWF reader
     *
     * @param SwfReader $reader
     * @param int<1, 2> $version The version of the ImportAssets tag
     *
     * @return self
     */
    public static function read(SwfReader $reader, int $version): self
    {
        $url = $reader->readNullTerminatedString();

        if ($version === 2) {
            $reader->skipBytes(1); // Reserved, must be 1
            $reader->skipBytes(1); // Reserved, must be 0
        }

        $characters = [];
        $count = $reader->readUI16();

        for ($i = 0; $i < $count; $i++) {
            $characters[$reader->readUI16()] = $reader->readNullTerminatedString();
        }

        return new ImportAssetsTag(
            version: $version,
            url: $url,
            characters: $characters,
        );
    }
}
