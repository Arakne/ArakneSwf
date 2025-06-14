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

use function sprintf;

/**
 * Structure for store color
 * Alpha channel is optional
 *
 * All values are int between 0 and 255
 */
final readonly class Color
{
    public function __construct(
        public int $red,
        public int $green,
        public int $blue,
        public ?int $alpha = null,
    ) {}

    /**
     * Get the color as a hex string, prefixed with #
     *
     * Note: this method does not include the alpha channel
     */
    public function hex(): string
    {
        return sprintf('#%02x%02x%02x', $this->red, $this->green, $this->blue);
    }

    /**
     * Get the opacity of the color
     * If the alpha channel is not set, the opacity is 1.0
     *
     * @return float The opacity of the color, between 0.0 and 1.0
     */
    public function opacity(): float
    {
        return $this->alpha !== null ? $this->alpha / 255 : 1.0;
    }

    /**
     * Check if the current color has transparency (i.e. alpha channel is set, and not 255)
     */
    public function hasTransparency(): bool
    {
        return $this->alpha !== null && $this->alpha < 255;
    }

    public function __toString(): string
    {
        return $this->hex();
    }

    public function transform(ColorTransform $colorTransform): self
    {
        return $colorTransform->transform($this);
    }

    public static function readRgb(SwfReader $reader): self
    {
        return new Color(
            $reader->readUI8(),
            $reader->readUI8(),
            $reader->readUI8(),
        );
    }

    public static function readRgba(SwfReader $reader): self
    {
        return new Color(
            $reader->readUI8(),
            $reader->readUI8(),
            $reader->readUI8(),
            $reader->readUI8(),
        );
    }
}
