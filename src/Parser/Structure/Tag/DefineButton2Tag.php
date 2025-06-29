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
use Arakne\Swf\Parser\Structure\Record\ButtonCondAction;
use Arakne\Swf\Parser\Structure\Record\ButtonRecord;
use Arakne\Swf\Parser\SwfReader;

final readonly class DefineButton2Tag
{
    public const int TYPE = 34;

    public function __construct(
        public int $buttonId,
        public bool $trackAsMenu,
        public int $actionOffset,

        /** @var list<ButtonRecord> */
        public array $characters,

        /** @var list<mixed> */
        public array $actions,
    ) {}

    /**
     * Read a DefineButton2Tag from the reader.
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end byte offset of the tag in the SWF file
     *
     * @return self
     *
     * @throws ParserOutOfBoundException
     * @throws ParserInvalidDataException
     */
    public static function read(SwfReader $reader, int $end): self
    {
        $buttonId = $reader->readUI16();
        $reader->skipBits(7); // Reserved, must be 0
        $taskAsMenu = $reader->readBool();
        $actionOffset = $reader->readUI16();
        $characters = ButtonRecord::readCollection($reader, 2);
        $actions = $actionOffset !== 0 ? ButtonCondAction::readCollection($reader, $end) : [];

        return new DefineButton2Tag(
            buttonId: $buttonId,
            trackAsMenu: $taskAsMenu,
            actionOffset: $actionOffset,
            characters: $characters,
            actions: $actions,
        );
    }
}
