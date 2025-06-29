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

use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Error\ParserOutOfBoundException;
use Arakne\Swf\Parser\SwfReader;

final readonly class ExportAssetsTag
{
    public const int ID = 56;

    public function __construct(
        /**
         * Map of exported character IDs to their names.
         *
         * @var array<non-negative-int, string>
         */
        public array $characters,
    ) {}

    /**
     * Read an ExportAssetsTag from the SWF reader
     *
     * @param SwfReader $reader
     *
     * @return self
     *
     * @throws ParserOutOfBoundException
     * @throws ParserInvalidDataException
     */
    public static function read(SwfReader $reader): self
    {
        $characters = [];
        $count = $reader->readUI16();

        for ($i = 0; $i < $count && $reader->offset < $reader->end; $i++) {
            $characters[$reader->readUI16()] = $reader->readNullTerminatedString();
        }

        return new ExportAssetsTag($characters);
    }
}
