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

use Arakne\Swf\Parser\Error\ParserOutOfBoundException;
use Arakne\Swf\Parser\SwfReader;

final readonly class CSMTextSettingsTag
{
    public const int ID = 74;

    public function __construct(
        public int $textId,
        public int $useFlashType,
        public int $gridFit,
        public float $thickness,
        public float $sharpness,
    ) {}

    /**
     * Read a CSMTextSettings tag from the SWF reader
     *
     * @param SwfReader $reader
     *
     * @return self
     * @throws ParserOutOfBoundException
     */
    public static function read(SwfReader $reader): self
    {
        $textId = $reader->readUI16();
        $useFlashType = $reader->readUB(2);
        $gridFit = $reader->readUB(3);
        $reader->skipBits(3); // Reserved
        $thickness = $reader->readFloat();
        $sharpness = $reader->readFloat();
        $reader->skipBytes(1); // Reserved

        return new CSMTextSettingsTag(
            textId: $textId,
            useFlashType: $useFlashType,
            gridFit: $gridFit,
            thickness: $thickness,
            sharpness: $sharpness,
        );
    }
}
