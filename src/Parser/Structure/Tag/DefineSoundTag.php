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

final readonly class DefineSoundTag
{
    public const int TYPE = 14;

    public function __construct(
        public int $soundId,
        public int $soundFormat,
        public int $soundRate,
        /**
         * Named SoundSize on spec.
         * 0 = 8 bits, 1 = 16 bits
         */
        public bool $is16Bits,

        /**
         * Named SoundType on spec.
         * 0 = mono, 1 = stereo
         */
        public bool $stereo,
        public int $soundSampleCount,
        public string $soundData,
    ) {}

    /**
     * Read a DefineSoundTag from the given reader
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end byte offset of the tag
     * @return self
     */
    public static function read(SwfReader $reader, int $end): self
    {
        $soundId = $reader->readUI16();

        $flags = $reader->readUI8();
        $format = ($flags >> 4) & 0x0F; // 4 bits for sound format
        $rate   = ($flags >> 2) & 0x03; // 2 bits for sound rate
        $is16Bit = ($flags & 0b00000010) !== 0;
        $stereo  = ($flags & 0b00000001) !== 0;

        $sampleCount = $reader->readUI32();
        $data = $reader->readBytesTo($end);

        return new self(
            soundId: $soundId,
            soundFormat: $format,
            soundRate: $rate,
            is16Bits: $is16Bit,
            stereo: $stereo,
            soundSampleCount: $sampleCount,
            soundData: $data
        );
    }
}
