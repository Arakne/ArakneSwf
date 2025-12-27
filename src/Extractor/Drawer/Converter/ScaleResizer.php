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

use function assert;

/**
 * Resizes an image by a given scale factor.
 */
final readonly class ScaleResizer implements ImageResizerInterface
{
    public function __construct(
        /**
         * The scale factor. Must be greater than 0.0
         */
        private float $scale,
    ) {
        assert($this->scale > 0.0);
    }

    #[Override]
    public function scale(float $width, float $height): array
    {
        return [$width * $this->scale, $height * $this->scale];
    }
}
