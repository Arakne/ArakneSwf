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
use Arakne\Swf\Parser\Structure\Record\SoundInfo;
use Arakne\Swf\Parser\SwfReader;

final readonly class DefineButtonSoundTag
{
    public const int TYPE = 17;

    public function __construct(
        public int $buttonId,
        public int $buttonSoundChar0,
        public ?SoundInfo $buttonSoundInfo0,
        public int $buttonSoundChar1,
        public ?SoundInfo $buttonSoundInfo1,
        public int $buttonSoundChar2,
        public ?SoundInfo $buttonSoundInfo2,
        public int $buttonSoundChar3,
        public ?SoundInfo $buttonSoundInfo3,
    ) {}

    /**
     * Read a DefineButtonSoundTag from the given reader
     *
     * @param SwfReader $reader
     *
     * @return self
     * @throws ParserOutOfBoundException
     */
    public static function read(SwfReader $reader): self
    {
        return new DefineButtonSoundTag(
            buttonId: $reader->readUI16(),
            buttonSoundChar0: $char0 = $reader->readUI16(),
            buttonSoundInfo0: $char0 !== 0 ? SoundInfo::read($reader) : null,
            buttonSoundChar1: $char1 = $reader->readUI16(),
            buttonSoundInfo1: $char1 !== 0 ? SoundInfo::read($reader) : null,
            buttonSoundChar2: $char2 = $reader->readUI16(),
            buttonSoundInfo2: $char2 !== 0 ? SoundInfo::read($reader) : null,
            buttonSoundChar3: $char3 = $reader->readUI16(),
            buttonSoundInfo3: $char3 !== 0 ? SoundInfo::read($reader) : null,
        );
    }
}
