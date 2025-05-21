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

namespace Arakne\Swf\Extractor\Drawer\Converter;

use Override;

use function max;

/**
 * Resizes an image to fit into a given size, keeping the aspect ratio.
 *
 * The highest dimension (i.e. height, or width) will be resized to the given size,
 * and the other dimension will be resized proportionally.
 */
final readonly class FitSizeResizer implements ImageResizerInterface
{
    public function __construct(
        /** @var positive-int */
        public int $width,
        /** @var positive-int */
        public int $height,
    ) {}

    #[Override]
    public function scale(float $width, float $height): array
    {
        $factor = max(
            $width / $this->width,
            $height / $this->height,
        );

        return [
            ($width / $factor),
            ($height / $factor),
        ];
    }
}
