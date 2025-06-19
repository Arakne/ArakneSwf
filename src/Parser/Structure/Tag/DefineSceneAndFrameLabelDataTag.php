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

final readonly class DefineSceneAndFrameLabelDataTag
{
    public const int TYPE = 86;

    public function __construct(
        /** @var list<int> */
        public array $sceneOffsets,

        /** @var list<string> */
        public array $sceneNames,

        /** @var list<int> */
        public array $frameNumbers,

        /** @var list<string> */
        public array $frameLabels,
    ) {}

    /**
     * Read a DefineSceneAndFrameLabelData tag from the SWF reader
     *
     * @param SwfReader $reader
     *
     * @return self
     */
    public static function read(SwfReader $reader): self
    {
        $sceneOffsets = [];
        $sceneNames = [];
        $sceneCount = $reader->readEncodedU32();
        for ($i = 0; $i < $sceneCount; $i++) {
            $sceneOffsets[] = $reader->readEncodedU32();
            $sceneNames[] = $reader->readNullTerminatedString();
        }

        $frameNumbers = [];
        $frameLabels = [];
        $frameLabelCount = $reader->readEncodedU32();
        for ($i = 0; $i < $frameLabelCount; $i++) {
            $frameNumbers[] = $reader->readEncodedU32();
            $frameLabels[] = $reader->readNullTerminatedString();
        }

        return new DefineSceneAndFrameLabelDataTag(
            sceneOffsets: $sceneOffsets,
            sceneNames: $sceneNames,
            frameNumbers: $frameNumbers,
            frameLabels: $frameLabels,
        );
    }
}
