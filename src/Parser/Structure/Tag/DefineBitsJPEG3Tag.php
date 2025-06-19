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

use Arakne\Swf\Parser\Structure\Record\ImageDataType;
use Arakne\Swf\Parser\SwfReader;
use RuntimeException;

use function assert;
use function gzuncompress;
use function sprintf;

final readonly class DefineBitsJPEG3Tag implements DefineBitsJPEGTagInterface
{
    public const int TYPE = 35;

    public ImageDataType $type;

    public function __construct(
        public int $characterId,

        /**
         * Raw image data.
         * Can be JPEG, PNG, or GIF89.
         */
        public string $imageData,

        /**
         * Uncompressed alpha data as byte array.
         * Each byte is the opacity of the corresponding pixel in the {@see $imageData}.
         * The length of this array must be equal to the decoded image width * height.
         *
         * Note: this field is only present if the {@see $imageData} is a JPEG image.
         */
        public string $alphaData,
    ) {
        $this->type = ImageDataType::resolve($this->imageData);
    }

    /**
     * Read a DefineBitsJPEG3Tag from the given reader
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end byte position of the tag.
     * @return self
     */
    public static function read(SwfReader $reader, int $end): self
    {
        $characterId = $reader->readUI16();
        $imageData = $reader->readBytes($reader->readUI32());
        $alphaDataLength = $end - $reader->offset;
        assert($alphaDataLength >= 0);

        return new DefineBitsJPEG3Tag(
            characterId: $characterId,
            imageData: $imageData,
            alphaData: gzuncompress($reader->readBytes($alphaDataLength)) ?: throw new RuntimeException(sprintf('Invalid ZLIB data')), // ZLIB uncompress alpha channel
        );
    }
}
