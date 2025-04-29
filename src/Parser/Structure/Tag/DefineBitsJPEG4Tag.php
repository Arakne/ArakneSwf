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

use Arakne\Swf\Parser\Structure\Record\ImageDataType;

final readonly class DefineBitsJPEG4Tag implements DefineBitsJPEGTagInterface
{
    public const int ID = 90;

    public ImageDataType $type;

    public function __construct(
        public int $characterId,
        public int $deblockParam,

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
}
