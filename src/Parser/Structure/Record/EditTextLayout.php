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

namespace Arakne\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\SwfReader;

final readonly class EditTextLayout
{
    public function __construct(
        public int $align,
        public int $leftMargin,
        public int $rightMargin,
        public int $indent,
        public int $leading,
    ) {}

    /**
     * Read an EditTextLayout structure from the given reader.
     *
     * @param SwfReader $reader
     *
     * @return self
     */
    public static function read(SwfReader $reader): self
    {
        return new self(
            align: $reader->readUI8(),
            leftMargin: $reader->readUI16(),
            rightMargin: $reader->readUI16(),
            indent: $reader->readUI16(),
            leading: $reader->readSI16(),
        );
    }
}
