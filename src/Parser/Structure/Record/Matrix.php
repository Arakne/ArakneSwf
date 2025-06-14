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

use function round;
use function var_dump;

/**
 * Represents a 2D transformation matrix
 */
final readonly class Matrix
{
    public function __construct(
        /**
         * Horizontal scaling factor
         * Parameter A in the matrix
         */
        public float $scaleX = 1.0,

        /**
         * Vertical scaling factor
         * Parameter D in the matrix
         */
        public float $scaleY = 1.0,

        /**
         * First skew factor
         * Parameter B in the matrix
         */
        public float $rotateSkew0 = .0,

        /**
         * Second skew factor
         * Parameter C in the matrix
         */
        public float $rotateSkew1 = .0,

        /**
         * X-axis translation, in twips (1/20th of a pixel)
         * Parameter E or Tx in the matrix
         */
        public int $translateX = 0,

        /**
         * Y-axis translation, in twips (1/20th of a pixel)
         * Parameter F or Ty in the matrix
         */
        public int $translateY = 0,
    ) {}

    /**
     * Apply X and Y translation to the matrix and return a new matrix
     * This method will take in account the current matrix values to transform the translation before applying it
     *
     * @param int $x
     * @param int $y
     *
     * @return self The new matrix
     */
    public function translate(int $x, int $y): self
    {
        $translateX = (int) round($this->scaleX * $x + $this->rotateSkew1 * $y + $this->translateX);
        $translateY = (int) round($this->rotateSkew0 * $x + $this->scaleY * $y + $this->translateY);

        return new self($this->scaleX, $this->scaleY, $this->rotateSkew0, $this->rotateSkew1, $translateX, $translateY);
    }

    /**
     * Apply the matrix to the given X and Y coordinates and return the new X coordinate
     *
     * @param int $x The current X position
     * @param int $y The current Y position
     *
     * @return int The new X position
     */
    public function transformX(int $x, int $y): int
    {
        return (int) round($this->scaleX * $x + $this->rotateSkew1 * $y + $this->translateX);
    }

    /**
     * Apply the matrix to the given X and Y coordinates and return the new Y coordinate
     *
     * @param int $x The current X position
     * @param int $y The current Y position
     *
     * @return int The new Y position
     */
    public function transformY(int $x, int $y): int
    {
        return (int) round($this->rotateSkew0 * $x + $this->scaleY * $y + $this->translateY);
    }

    /**
     * Get the SVG matrix representation
     *
     * @param bool $undoTwipScale If true, scaling factors will be divided by 20 (to remove conversion to twips)
     */
    public function toSvgTransformation(bool $undoTwipScale = false): string
    {
        $scaleX = $this->scaleX;
        $scaleY = $this->scaleY;
        $rotateSkew0 = $this->rotateSkew0;
        $rotateSkew1 = $this->rotateSkew1;

        if ($undoTwipScale) {
            $scaleX /= 20;
            $scaleY /= 20;
            $rotateSkew0 /= 20;
            $rotateSkew1 /= 20;
        }

        return 'matrix(' . round($scaleX, 4) . ', ' . round($rotateSkew0, 4) . ', ' . round($rotateSkew1, 4) . ', ' . round($scaleY, 4) . ', ' . round($this->translateX / 20, 4) . ', ' . round($this->translateY / 20, 4) . ')';
    }

    public static function read(SwfReader $reader): self
    {
        $scaleX = 1.0;
        $scaleY = 1.0;
        $rotateSkew0 = 0.0;
        $rotateSkew1 = 0.0;
        $translateX = 0;
        $translateY = 0;

        if ($reader->readBool()) {
            $nScaleBits = $reader->readUB(5);
            $scaleX = $reader->readFB($nScaleBits);
            $scaleY = $reader->readFB($nScaleBits);
        }

        if ($reader->readBool()) {
            $nRotateBits = $reader->readUB(5);
            $rotateSkew0 = $reader->readFB($nRotateBits);
            $rotateSkew1 = $reader->readFB($nRotateBits);
        }

        if (($nTranslateBits = $reader->readUB(5)) != 0) {
            $translateX = $reader->readSB($nTranslateBits);
            $translateY = $reader->readSB($nTranslateBits);
        }

        $reader->alignByte();

        return new Matrix($scaleX, $scaleY, $rotateSkew0, $rotateSkew1, $translateX, $translateY);
    }
}
