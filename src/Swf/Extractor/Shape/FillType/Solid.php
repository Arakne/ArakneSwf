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

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Override;

final readonly class Solid implements FillTypeInterface
{
    public function __construct(
        public Color $color
    ) {}

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        return new self($colorTransform->transform($this->color));
    }

    #[Override]
    public function hash(): string
    {
        $color = $this->color;

        return 'S'.(($color->red << 24) | ($color->green << 16) | ($color->blue << 8) | ($color->alpha ?? 255));
    }
}
