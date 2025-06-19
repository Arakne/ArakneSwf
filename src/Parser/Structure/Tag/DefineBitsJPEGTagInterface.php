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

interface DefineBitsJPEGTagInterface
{
    /**
     * The stored image data type.
     */
    public ImageDataType $type { get; }

    /**
     * Raw image data.
     * Can be JPEG, PNG, or GIF89.
     *
     * Use {@see self::$type} to get the image format.
     */
    public string $imageData { get; }

    /**
     * Uncompressed alpha data as byte array.
     * Each byte is the opacity of the corresponding pixel in the {@see $imageData}.
     * The length of this array must be equal to the decoded image width * height.
     *
     * Note: this field is only present if the {@see $imageData} is a JPEG image.
     */
    public ?string $alphaData { get; }
}
