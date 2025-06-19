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

final readonly class SoundStreamHeadTag
{
    public const int TYPE_V1 = 18;
    public const int TYPE_V2 = 45;

    public function __construct(
        public int $version,
        public int $playbackSoundRate,
        public int $playbackSoundSize,
        public int $playbackSoundType,
        public int $streamSoundCompression,
        public int $streamSoundRate,
        public int $streamSoundSize,
        public int $streamSoundType,
        public int $streamSoundSampleCount,
        public ?int $latencySeek,
    ) {}

    /**
     * Read a SoundStreamHead or SoundStreamHead2 tag from the given reader
     *
     * @param SwfReader $reader
     * @param int<1, 2> $version The version of the tag, either 1 or 2
     *
     * @return self
     */
    public static function read(SwfReader $reader, int $version): self
    {
        $flags = $reader->readUI8();
        // 4 bits reserved
        $playbackSoundRate = ($flags >> 2) & 3; // 2 bits for sound rate
        $playback16Bits  = ($flags & 0b00000010) !== 0;
        $playbackStereo    = ($flags & 0b00000001) !== 0;

        $flags = $reader->readUI8();
        $compression = ($flags >> 4) & 0x0F; // 4 bits for sound compression
        $streamSoundRate = ($flags >> 2) & 0x03; // 2 bits for sound rate
        $stream16Bits  = ($flags & 0b00000010) !== 0;
        $streamStereo  = ($flags & 0b00000001) !== 0;

        $streamSoundSampleCount = $reader->readUI16();
        $latencySeek = $compression === 2 ? $reader->readSI16() : null;

        return new self(
            version: $version,
            playbackSoundRate: $playbackSoundRate,
            playbackSoundSize: $playback16Bits ? 1 : 0,
            playbackSoundType: $playbackStereo ? 1 : 0,
            streamSoundCompression: $compression,
            streamSoundRate: $streamSoundRate,
            streamSoundSize: $stream16Bits ? 1 : 0,
            streamSoundType: $streamStereo ? 1 : 0,
            streamSoundSampleCount: $streamSoundSampleCount,
            latencySeek: $latencySeek,
        );
    }
}
