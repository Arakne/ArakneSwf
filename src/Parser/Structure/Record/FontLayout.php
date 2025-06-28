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

final readonly class FontLayout
{
    public function __construct(
        public int $ascent,
        public int $descent,
        public int $leading,

        /**
         * @var list<int>
         */
        public array $advanceTable,

        /**
         * @var list<Rectangle>
         */
        public array $boundsTable,

        /**
         * @var list<KerningRecord>
         */
        public array $kerningTable = [],
    ) {}

    /**
     * Read a FontLayout record from the SWF reader
     *
     * @param SwfReader $reader
     * @param non-negative-int $numGlyphs Number of glyphs in the font
     * @param bool $wideCodes Use wide codes for glyphs (16-bit codes instead of 8-bit)
     * @return self
     */
    public static function read(SwfReader $reader, int $numGlyphs, bool $wideCodes): self
    {
        $ascent = $reader->readSI16();
        $descent = $reader->readSI16();
        $leading = $reader->readSI16();

        $advanceTable = [];
        for ($i = 0; $i < $numGlyphs; ++$i) {
            $advanceTable[] = $reader->readSI16();
        }

        $boundsTable = [];
        for ($i = 0; $i < $numGlyphs; ++$i) {
            $boundsTable[] = Rectangle::read($reader);
        }

        $kerningCount = $reader->readUI16();
        $kerningTable = [];
        for ($i = 0; $i < $kerningCount; ++$i) {
            $code1 = $wideCodes ? $reader->readUI16() : $reader->readUI8();
            $code2 = $wideCodes ? $reader->readUI16() : $reader->readUI8();
            $adjustment = $reader->readSI16();

            $kerningTable[] = new KerningRecord($code1, $code2, $adjustment);
        }

        return new self(
            $ascent,
            $descent,
            $leading,
            $advanceTable,
            $boundsTable,
            $kerningTable
        );
    }
}
