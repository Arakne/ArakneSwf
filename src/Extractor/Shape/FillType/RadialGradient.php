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
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Extractor\Shape\FillType;

use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Override;

use function hash;
use function json_encode;

final readonly class RadialGradient implements FillTypeInterface
{
    public function __construct(
        public Matrix $matrix,
        public Gradient $gradient,
    ) {}

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        return new self(
            $this->matrix,
            $this->gradient->transformColors($colorTransform),
        );
    }

    #[Override]
    public function hash(): string
    {
        return 'R' . hash('xxh128', json_encode($this));
    }
}
