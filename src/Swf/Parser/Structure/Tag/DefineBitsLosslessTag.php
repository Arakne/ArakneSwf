<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Arakne-Swf: derived from SWF.php
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\ImageBitmapType;

final readonly class DefineBitsLosslessTag
{
    public const int V1_ID = 20;
    public const int V2_ID = 36;

    public const int FORMAT_8_BIT = 3;
    /** Only on v1 */
    public const int FORMAT_15_BIT = 4;
    /** Only on v1 */
    public const int FORMAT_24_BIT = 5;
    /** Only on v2 */
    public const int FORMAT_32_BIT = 5;

    public function __construct(
        public int $version,
        public int $characterId,
        public int $bitmapFormat,
        public int $bitmapWidth,
        public int $bitmapHeight,
        public ?string $colorTable,

        /**
         * Uncompressed pixel data
         * The content depends on the {@see $bitmapFormat}:
         * - {@see FORMAT_8_BIT}: 1 byte per pixel, use the {@see $colorTable} to get the color
         * - {@see FORMAT_15_BIT}: 2 bytes per pixel, 5 bits for red, 5 bits for green, 5 bits for blue (first bit is ignored)
         * - {@see FORMAT_24_BIT}: 3 bytes per pixel, 8 bits for red, 8 bits for green, 8 bits for blue (first byte is ignored)
         * - {@see FORMAT_32_BIT}: 4 bytes per pixel, 8 bits for alpha, 8 bits for red, 8 bits for green, 8 bits for blue
         */
        public string $pixelData,
    ) {}

    /**
     * Get the sorted image format
     */
    public function type(): ImageBitmapType
    {
        return ImageBitmapType::fromTag($this);
    }
}
