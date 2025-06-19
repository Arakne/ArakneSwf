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

use Arakne\Swf\Parser\SwfReader;

/**
 * Attach information about the product that created the SWF file.
 *
 * Note: this tag is not documented in the official SWF documentation.
 *
 * @see https://www.m2osw.com/swf_tag_productinfo
 */
final readonly class ProductInfo
{
    public const int TYPE = 41;

    public function __construct(
        public int $productId,
        public int $edition,
        public int $majorVersion,
        public int $minorVersion,
        public int $buildNumber,
        public int $compilationDate,
    ) {}

    /**
     * Read a ProductInfo tag from the given reader.
     *
     * @param SwfReader $reader
     * @return self
     */
    public static function read(SwfReader $reader): self
    {
        return new ProductInfo(
            productId: $reader->readUI32(),
            edition: $reader->readUI32(),
            majorVersion: $reader->readUI8(),
            minorVersion: $reader->readUI8(),
            buildNumber: $reader->readSI64(),
            compilationDate: $reader->readSI64(),
        );
    }
}
