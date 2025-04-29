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

namespace Arakne\Swf\Parser\Structure\Record;

/**
 * Structure for store color transform
 * Multiplier terms should be divided by 256
 */
final readonly class ColorTransform
{
    public function __construct(
        public int $redMult = 256,
        public int $greenMult = 256,
        public int $blueMult = 256,
        public int $alphaMult = 256,
        public int $redAdd = 0,
        public int $greenAdd = 0,
        public int $blueAdd = 0,
        public int $alphaAdd = 0,
    ) {}

    /**
     * Apply the transformation to a color and return the result
     *
     * @param Color $color
     * @return Color
     */
    public function transform(Color $color): Color
    {
        $red = $color->red * $this->redMult / 256 + $this->redAdd;
        $green = $color->green * $this->greenMult / 256 + $this->greenAdd;
        $blue = $color->blue * $this->blueMult / 256 + $this->blueAdd;
        $alpha = ($color->alpha ?? 255) * $this->alphaMult / 256 + $this->alphaAdd;

        if ($red < 0) {
            $red = 0;
        } elseif ($red > 255) {
            $red = 255;
        }

        if ($green < 0) {
            $green = 0;
        } elseif ($green > 255) {
            $green = 255;
        }

        if ($blue < 0) {
            $blue = 0;
        } elseif ($blue > 255) {
            $blue = 255;
        }

        if ($alpha < 0) {
            $alpha = 0;
        } elseif ($alpha > 255) {
            $alpha = 255;
        }

        return new Color((int) $red, (int) $green, (int) $blue, (int) $alpha);
    }
}
