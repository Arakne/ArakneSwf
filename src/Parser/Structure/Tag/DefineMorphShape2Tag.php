<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Arakne-Swf: derived from SWF.php
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Record\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\StyleChangeRecord;

final readonly class DefineMorphShape2Tag
{
    public function __construct(
        public int $characterId,
        public Rectangle $startBounds,
        public Rectangle $endBounds,
        public Rectangle $startEdgeBounds,
        public Rectangle $endEdgeBounds,
        public bool $usesNonScalingStrokes,
        public bool $usesScalingStrokes,
        public int $offset,

        /** @var list<mixed> */
        public array $fillStyles,

        /** @var list<mixed> */
        public array $lineStyles,

        /** @var list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord> */
        public array $startEdges,

        /** @var list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord> */
        public array $endEdges,
    ) {}
}
