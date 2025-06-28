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

use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphFillStyle;
use Arakne\Swf\Parser\Structure\Record\MorphShape\MorphLineStyle;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Record\Shape\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\ShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StyleChangeRecord;
use Arakne\Swf\Parser\SwfReader;

final readonly class DefineMorphShapeTag
{
    public const int TYPE = 46;

    public function __construct(
        public int $characterId,
        public Rectangle $startBounds,
        public Rectangle $endBounds,
        public int $offset,

        /** @var list<MorphFillStyle> */
        public array $fillStyles,

        /** @var list<MorphLineStyle> */
        public array $lineStyles,

        /** @var list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord> */
        public array $startEdges,

        /** @var list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord> */
        public array $endEdges,
    ) {}

    /**
     * Read a DefineMorphShapeTag from the given reader.
     *
     * @param SwfReader $reader
     * @return self
     */
    public static function read(SwfReader $reader): self
    {
        // The shape version only change the style records, and because morph shapes does not use basic styles,
        // we can safely ignore the version here
        return new DefineMorphShapeTag(
            characterId: $reader->readUI16(),
            startBounds: Rectangle::read($reader),
            endBounds: Rectangle::read($reader),
            offset: $reader->readUI32(),
            fillStyles: MorphFillStyle::readCollection($reader),
            lineStyles: MorphLineStyle::readCollection($reader),
            startEdges: ShapeRecord::readCollection($reader, 1),
            endEdges: ShapeRecord::readCollection($reader, 1),
        );
    }
}
