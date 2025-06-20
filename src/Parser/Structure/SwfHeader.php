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

namespace Arakne\Swf\Parser\Structure;

use Arakne\Swf\Parser\Structure\Record\Rectangle;

final readonly class SwfHeader
{
    public function __construct(
        /**
         * @var "FWS"|"CWS"
         */
        public string $signature,

        /**
         * The version of the SWF file
         *
         * @var non-negative-int
         */
        public int $version,

        /**
         * The length of the SWF file, in bytes
         * If the file is compressed (CWS), this is the length of the uncompressed file
         *
         * @var positive-int
         */
        public int $fileLength,
        public Rectangle $frameSize,
        public float $frameRate,

        /**
         * @var non-negative-int
         */
        public int $frameCount,
    ) {}
}
