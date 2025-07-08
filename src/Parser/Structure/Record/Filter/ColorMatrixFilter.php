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

namespace Arakne\Swf\Parser\Structure\Record\Filter;

use Arakne\Swf\Parser\SwfReader;
use Override;

use function assert;
use function count;

final readonly class ColorMatrixFilter extends Filter
{
    public const int FILTER_ID = 6;

    public function __construct(
        /**
         * @var list<float> Size must be 20
         */
        public array $matrix,
    ) {
        assert(count($this->matrix) === 20);
    }

    #[Override]
    protected static function read(SwfReader $reader): static
    {
        $matrix = [];

        for ($i = 0; $i < 20; ++$i) {
            $matrix[] = $reader->readFloat();
        }

        return new ColorMatrixFilter($matrix);
    }
}
