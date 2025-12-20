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

use Arakne\Swf\Error\Errors;
use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\SwfReader;

use function assert;
use function sprintf;

/**
 * Structure representing a rectangle (two points)
 * Coordinates are in twips (1/20th of a pixel), and can be negative
 */
final readonly class Rectangle
{
    public function __construct(
        public int $xmin,
        public int $xmax,
        public int $ymin,
        public int $ymax,
    ) {
        assert($this->xmin <= $this->xmax);
        assert($this->ymin <= $this->ymax);
    }

    public function width(): int
    {
        return $this->xmax - $this->xmin;
    }

    public function height(): int
    {
        return $this->ymax - $this->ymin;
    }

    /**
     * Apply a transformation matrix to the rectangle and return the new rectangle
     *
     * The matrix will be applied to the 4 corners of the rectangle,
     * and the new rectangle will be the bounding box of the 4 new points
     *
     * @return self The new rectangle
     */
    public function transform(Matrix $matrix): self
    {
        $xmin = PHP_INT_MAX;
        $xmax = PHP_INT_MIN;
        $ymin = PHP_INT_MAX;
        $ymax = PHP_INT_MIN;

        $x = $matrix->transformX($this->xmin, $this->ymin);
        $y = $matrix->transformY($this->xmin, $this->ymin);

        if ($x < $xmin) {
            $xmin = $x;
        }

        if ($x > $xmax) {
            $xmax = $x;
        }

        if ($y < $ymin) {
            $ymin = $y;
        }

        if ($y > $ymax) {
            $ymax = $y;
        }

        $x = $matrix->transformX($this->xmax, $this->ymin);
        $y = $matrix->transformY($this->xmax, $this->ymin);

        if ($x < $xmin) {
            $xmin = $x;
        }

        if ($x > $xmax) {
            $xmax = $x;
        }

        if ($y < $ymin) {
            $ymin = $y;
        }

        if ($y > $ymax) {
            $ymax = $y;
        }

        $x = $matrix->transformX($this->xmin, $this->ymax);
        $y = $matrix->transformY($this->xmin, $this->ymax);

        if ($x < $xmin) {
            $xmin = $x;
        }

        if ($x > $xmax) {
            $xmax = $x;
        }

        if ($y < $ymin) {
            $ymin = $y;
        }

        if ($y > $ymax) {
            $ymax = $y;
        }

        $x = $matrix->transformX($this->xmax, $this->ymax);
        $y = $matrix->transformY($this->xmax, $this->ymax);

        if ($x < $xmin) {
            $xmin = $x;
        }

        if ($x > $xmax) {
            $xmax = $x;
        }

        if ($y < $ymin) {
            $ymin = $y;
        }

        if ($y > $ymax) {
            $ymax = $y;
        }

        return new self($xmin, $xmax, $ymin, $ymax);
    }

    /**
     * Create a rectangle that is the union of this rectangle and another one
     *
     * @param Rectangle $other
     * @return self
     */
    public function union(Rectangle $other): self
    {
        return new self(
            $this->xmin < $other->xmin ? $this->xmin : $other->xmin,
            $this->xmax > $other->xmax ? $this->xmax : $other->xmax,
            $this->ymin < $other->ymin ? $this->ymin : $other->ymin,
            $this->ymax > $other->ymax ? $this->ymax : $other->ymax,
        );
    }

    /**
     * Create a rectangle that encompasses all given rectangles
     * If no rectangles are given, a rectangle with all coordinates set to 0 will be returned
     *
     * @param self[] $rectangles Array of rectangles to merge
     * @return self
     */
    public static function merge(array $rectangles): self
    {
        if (!$rectangles) {
            return new self(0, 0, 0, 0);
        }

        $xmin = PHP_INT_MAX;
        $ymin = PHP_INT_MAX;
        $xmax = PHP_INT_MIN;
        $ymax = PHP_INT_MIN;

        foreach ($rectangles as $rectangle) {
            if ($rectangle->xmin < $xmin) {
                $xmin = $rectangle->xmin;
            }
            if ($rectangle->ymin < $ymin) {
                $ymin = $rectangle->ymin;
            }
            if ($rectangle->xmax > $xmax) {
                $xmax = $rectangle->xmax;
            }
            if ($rectangle->ymax > $ymax) {
                $ymax = $rectangle->ymax;
            }
        }

        return new self($xmin, $xmax, $ymin, $ymax);
    }

    public static function read(SwfReader $reader): self
    {
        $nbits = $reader->readUB(5);
        assert($nbits < 32);

        $xmin = $reader->readSB($nbits);
        $xmax = $reader->readSB($nbits);
        $ymin = $reader->readSB($nbits);
        $ymax = $reader->readSB($nbits);

        if ($xmin > $xmax) {
            if ($reader->errors & Errors::INVALID_DATA) {
                throw new ParserInvalidDataException(sprintf('Invalid rectangle: xmin (%d) is greater than xmax (%d)', $xmin, $xmax), $reader->offset);
            }

            $xmin = $xmax;
        }

        if ($ymin > $ymax) {
            if ($reader->errors & Errors::INVALID_DATA) {
                throw new ParserInvalidDataException(sprintf('Invalid rectangle: ymin (%d) is greater than ymax (%d)', $ymin, $ymax), $reader->offset);
            }

            $ymin = $ymax;
        }

        $ret = new Rectangle($xmin, $xmax, $ymin, $ymax);

        $reader->alignByte();

        return $ret;
    }
}
