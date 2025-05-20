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

use Imagick;

/**
 * Base type for resize Imagick images.
 */
interface ImageResizerInterface
{
    /**
     * Applies the resize operation to the given image.
     *
     * @param Imagick $image The image to resize.
     * @param string $svg The original SVG data.
     *
     * @return Imagick The resized image. Can be the same instance as the input image.
     */
    public function apply(Imagick $image, string $svg): Imagick;

    /**
     * Returns the width of the resized image.
     *
     * Note: Because SWF uses twips (1/20th of a pixel), size may be in decimal, so we use float.
     *
     * @param float $width The original width in pixels.
     * @param float $height The original height in pixels.
     *
     * @return list{float, float} The new width and height in pixels.
     */
    public function scale(float $width, float $height): array;
}
