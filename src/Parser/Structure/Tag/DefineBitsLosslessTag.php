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

use Arakne\Swf\Error\Errors;
use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Error\ParserOutOfBoundException;
use Arakne\Swf\Parser\Structure\Record\ImageBitmapType;
use Arakne\Swf\Parser\SwfReader;

use function sprintf;
use function substr;

final readonly class DefineBitsLosslessTag
{
    public const int TYPE_V1 = 20;
    public const int TYPE_V2 = 36;

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

        /**
         * Swf spec allow 0 width and height, but this is not a valid image
         *
         * @var non-negative-int
         */
        public int $bitmapWidth,

        /**
         * Swf spec allow 0 width and height, but this is not a valid image
         *
         * @var non-negative-int
         */
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

    /**
     * Read a DefineBitsLossless or DefineBitsLossless2 tag from the reader
     *
     * @param SwfReader $reader
     * @param int<1, 2> $version The version of the tag, 1 for DefineBitsLossless and 2 for DefineBitsLossless2
     * @param non-negative-int $end The end position of the tag in the stream, used to determine the end of the pixel data
     *
     * @return self
     * @throws ParserOutOfBoundException
     * @throws ParserInvalidDataException
     */
    public static function read(SwfReader $reader, int $version, int $end): self
    {
        $characterId = $reader->readUI16();
        $bitmapFormat = $reader->readUI8();
        $bitmapWidth = $reader->readUI16();
        $bitmapHeight = $reader->readUI16();

        if (($bitmapFormat < 3 || $bitmapFormat > 5) && $reader->errors & Errors::INVALID_DATA) {
            throw new ParserInvalidDataException(
                sprintf('Invalid bitmap format %d for DefineBitsLossless tag (version %d)', $bitmapFormat, $version),
                $reader->offset
            );
        }

        if ($bitmapFormat === self::FORMAT_8_BIT) {
            $colors = $reader->readUI8();
            $data = $reader->readZLibTo($end);
            $colorSize = $version > 1 ? 4 : 3; // 4 bytes for RGBA, 3 bytes for RGB
            $colorTableSize = $colorSize * ($colors + 1);

            $colorTable = substr($data, 0, $colorTableSize);
            $pixelData = substr($data, $colorTableSize);
        } else {
            $colorTable = null;
            $pixelData = $reader->readZLibTo($end);
        }

        return new DefineBitsLosslessTag(
            version: $version,
            characterId: $characterId,
            bitmapFormat: $bitmapFormat,
            bitmapWidth: $bitmapWidth,
            bitmapHeight: $bitmapHeight,
            colorTable: $colorTable,
            pixelData: $pixelData,
        );
    }
}
