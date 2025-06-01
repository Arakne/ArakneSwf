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

namespace Arakne\Swf\Extractor\Image;

use Arakne\Swf\Parser\Structure\Record\ImageDataType;

use function base64_encode;
use function sprintf;

/**
 * Store the image data with its type.
 */
final readonly class ImageData
{
    public function __construct(
        public ImageDataType $type,
        public string $data,
    ) {}

    /**
     * Convert the image data to a "data:type;base64,..." string,
     * so it can be used in href attributes or as a source for images.
     */
    public function toBase64Url(): string
    {
        return sprintf('data:%s;base64,%s', $this->type->mimeType(), base64_encode($this->data));
    }
}
