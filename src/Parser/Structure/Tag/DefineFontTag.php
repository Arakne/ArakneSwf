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
use Arakne\Swf\Parser\Structure\Record\Shape\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\ShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StyleChangeRecord;
use Arakne\Swf\Parser\SwfReader;

final readonly class DefineFontTag
{
    public const int TYPE_V1 = 10;

    public function __construct(
        public int $fontId,

        /** @var list<int> */
        public array $offsetTable,

        /** @var list<list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord>> */
        public array $glyphShapeData,
    ) {}

    /**
     * Read a DefineFontTag from the reader.
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
        $fontId = $reader->readUI16();

        // The first offset must point to the first glyph, and because each offset is 2 bytes,
        // the number of glyphs is the offset of the first glyph divided by 2.
        $numGlyphs = $reader->peekUI16() >> 1;

        $offsetTable = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            $offsetTable[] = $reader->readUI16();
        }

        $glyphShapeData = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            $glyphShapeData[] = ShapeRecord::readCollection($reader, 1);
        }

        return new DefineFontTag(
            fontId: $fontId,
            offsetTable: $offsetTable,
            glyphShapeData: $glyphShapeData,
        );
    }
}
