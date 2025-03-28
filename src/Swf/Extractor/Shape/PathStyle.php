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

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Shape\FillType\Bitmap;
use Arakne\Swf\Extractor\Shape\FillType\LinearGradient;
use Arakne\Swf\Extractor\Shape\FillType\RadialGradient;
use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;

/**
 * Define the drawing style of a path
 *
 * This style is common for line and fill paths
 * This object will also be used as key to allow merging paths with the same style
 */
final readonly class PathStyle
{
    private string $hash;

    public function __construct(
        /**
         * The fill style and color of the current path
         * If this value is null, the path should not be filled
         */
        public Solid|LinearGradient|RadialGradient|Bitmap|null $fill = null,

        /**
         * The line color of the current path
         * If this value is null, the path should not be stroked
         */
        public ?Color $lineColor = null,

        /**
         * The width of the line in twips
         *
         * This value should be divided by 20 to get the width in pixels
         * This value should be set only if the lineColor is set
         */
        public int $lineWidth = 0,

        /**
         * Does the edges should be added in reverse order?
         * This value should be true for style0 fill paths
         *
         * Note: this value is not used in the hash code, and its applied only on path building
         */
        public bool $reverse = false,
    ) {
        $this->hash = $this->fill?->hash() . '-' . self::colorHash($this->lineColor) . '-' . $this->lineWidth;
    }

    /**
     * Compute the hash code of the style to be used as key
     */
    public function hash(): string
    {
        return $this->hash;
    }

    private static function colorHash(Color|null $color): int
    {
        if ($color === null) {
            return -1;
        }

        return ($color->red << 24) | ($color->green << 16) | ($color->blue << 8) | ($color->alpha ?? 255);
    }

    public function transformColors(ColorTransform $colorTransform)
    {
        $fill = $this->fill?->transformColors($colorTransform);
        $lineColor = $this->lineColor?->transform($colorTransform);

        return new self(
            $fill,
            $lineColor,
            $this->lineWidth,
            $this->reverse
        );
    }
}
