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

final readonly class SymbolClassTag
{
    public const int TYPE = 76;

    public function __construct(
        /**
         * Map of symbol id (character id) to symbol name (AS3 class name).
         *
         * @var array<int, string>
         */
        public array $symbols,
    ) {}

    /**
     * Read a SymbolClass tag from the SWF reader
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
        $symbols = [];
        $count = $reader->readUI16();

        for ($i = 0; $i < $count; $i++) {
            $id = $reader->readUI16();
            $name = $reader->readNullTerminatedString();

            $symbols[$id] = $name;
        }

        return new SymbolClassTag($symbols);
    }
}
