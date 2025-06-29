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

use Arakne\Swf\Error\Errors;
use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Error\ParserOutOfBoundException;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Record\TextRecord;
use Arakne\Swf\Parser\SwfReader;

use function sprintf;

final readonly class DefineTextTag
{
    public const int TYPE_V1 = 11;
    public const int TYPE_V2 = 33;

    public function __construct(
        public int $version,
        public int $characterId,
        public Rectangle $textBounds,
        public Matrix $textMatrix,
        public int $glyphBits,
        public int $advanceBits,

        /** @var list<TextRecord> */
        public array $textRecords,
    ) {}

    /**
     * Read a DefineText or DefineTex2 from the reader.
     *
     * @param SwfReader $reader
     * @param int<1, 2> $version The version of the tag, either 1 or 2. The version 2 will handle the alpha channel in TextRecord.
     *
     * @return self
     *
     * @throws ParserOutOfBoundException
     * @throws ParserInvalidDataException
     */
    public static function read(SwfReader $reader, int $version): self
    {
        $characterId = $reader->readUI16();
        $textBounds = Rectangle::read($reader);
        $textMatrix = Matrix::read($reader);
        $glyphBits = $reader->readUI8();
        $advanceBits = $reader->readUI8();

        if ($glyphBits > 32 || $advanceBits > 32) {
            if ($reader->errors & Errors::INVALID_DATA) {
                throw new ParserInvalidDataException(
                    sprintf('Glyph bits (%d) or advance bits (%d) are out of bounds (0-32)', $glyphBits, $advanceBits),
                    $reader->offset
                );
            }

            $textRecords = [];
        } else {
            $textRecords = TextRecord::readCollection($reader, $glyphBits, $advanceBits, withAlpha: $version > 1);
        }

        return new DefineTextTag(
            version: $version,
            characterId: $characterId,
            textBounds: $textBounds,
            textMatrix: $textMatrix,
            glyphBits: $glyphBits,
            advanceBits: $advanceBits,
            textRecords: $textRecords,
        );
    }
}
