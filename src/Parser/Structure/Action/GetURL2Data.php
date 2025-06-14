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

final readonly class GetURL2Data
{
    public function __construct(
        public int $sendVarsMethod,
        public bool $loadTargetFlag,
        public bool $loadVariablesFlag,
    ) {}

    public static function read(SwfReader $reader): self
    {
        $flags = $reader->readUI8();

        $sendVarsMethod    = ($flags & 0b11000000) >> 6;
        // 4 bits reserved, must be 0
        $loadTargetFlag    = ($flags & 0b00000010) !== 0;
        $loadVariablesFlag = ($flags & 0b00000001) !== 0;

        return new GetURL2Data($sendVarsMethod, $loadTargetFlag, $loadVariablesFlag);
    }
}
