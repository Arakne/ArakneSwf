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

use function assert;

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

    public static function read(SwfReader $reader, bool $withAlpha): self
    {
        $hasAddTerms = $reader->readBool();
        $hasMultTerms = $reader->readBool();
        $nbits = $reader->readUB(4);
        assert($nbits < 16);

        $redMultTerm = 256;
        $greenMultTerm = 256;
        $blueMultTerm = 256;
        $alphaMultTerm = 256;
        $redAddTerm = 0;
        $greenAddTerm = 0;
        $blueAddTerm = 0;
        $alphaAddTerm = 0;

        if ($hasMultTerms) {
            $redMultTerm = $reader->readSB($nbits);
            $greenMultTerm = $reader->readSB($nbits);
            $blueMultTerm = $reader->readSB($nbits);

            if ($withAlpha) {
                $alphaMultTerm = $reader->readSB($nbits);
            }
        }

        if ($hasAddTerms) {
            $redAddTerm = $reader->readSB($nbits);
            $greenAddTerm = $reader->readSB($nbits);
            $blueAddTerm = $reader->readSB($nbits);

            if ($withAlpha) {
                $alphaAddTerm = $reader->readSB($nbits);
            }
        }

        $reader->alignByte();

        return new ColorTransform(
            $redMultTerm,
            $greenMultTerm,
            $blueMultTerm,
            $alphaMultTerm,
            $redAddTerm,
            $greenAddTerm,
            $blueAddTerm,
            $alphaAddTerm,
        );
    }
}
