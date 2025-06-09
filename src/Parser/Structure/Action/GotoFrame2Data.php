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

namespace Arakne\Swf\Parser\Structure\Action;

use Arakne\Swf\Parser\SwfReader;

final readonly class GotoFrame2Data
{
    public function __construct(
        public bool $sceneBiasFlag,
        public bool $playFlag,
        public ?int $sceneBias,
    ) {}

    public static function read(SwfReader $reader): self
    {
        $flags = $reader->readUI8();

        // 6bits reserved
        $sceneBiasFlag = ($flags & 0b00000010) !== 0;
        $playFlag      = ($flags & 0b00000001) !== 0;

        $sceneBias = $sceneBiasFlag ? $reader->readUI16() : null;

        return new GotoFrame2Data($sceneBiasFlag, $playFlag, $sceneBias);
    }
}
