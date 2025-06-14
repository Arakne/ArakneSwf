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

namespace Arakne\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\SwfReader;

final readonly class SoundInfo
{
    public function __construct(
        public bool $syncStop,
        public bool $syncNoMultiple,
        public ?int $inPoint,
        public ?int $outPoint,
        public ?int $loopCount,
        /**
         * @var list<SoundEnvelope>
         */
        public array $envelopes = [],
    ) {}

    /**
     * Read a single SoundInfo record.
     *
     * @param SwfReader $reader
     * @return self
     */
    public static function read(SwfReader $reader): self
    {
        $flags = $reader->readUI8();
        // 2 bits reserved
        $syncStop       = ($flags & 0b00100000) !== 0;
        $syncNoMultiple = ($flags & 0b00010000) !== 0;
        $hasEnvelope    = ($flags & 0b00001000) !== 0;
        $hasLoops       = ($flags & 0b00000100) !== 0;
        $hasOutPoint    = ($flags & 0b00000010) !== 0;
        $hasInPoint     = ($flags & 0b00000001) !== 0;

        return new SoundInfo(
            syncStop: $syncStop,
            syncNoMultiple: $syncNoMultiple,
            inPoint: $hasInPoint ? $reader->readUI32() : null,
            outPoint: $hasOutPoint ? $reader->readUI32() : null,
            loopCount: $hasLoops ? $reader->readUI16() : null,
            envelopes: $hasEnvelope ? self::readEnvelopes($reader) : [],
        );
    }

    /**
     * @param SwfReader $reader
     * @return list<SoundEnvelope>
     */
    private static function readEnvelopes(SwfReader $reader): array
    {
        $count = $reader->readUI8();
        $envelopes = [];

        for ($i = 0; $i < $count; $i++) {
            $envelopes[] = new SoundEnvelope(
                pos44: $reader->readUI32(),
                leftLevel: $reader->readUI16(),
                rightLevel: $reader->readUI16(),
            );
        }

        return $envelopes;
    }
}
