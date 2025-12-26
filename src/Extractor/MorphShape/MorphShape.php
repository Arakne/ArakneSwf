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

namespace Arakne\Swf\Extractor\MorphShape;

use Arakne\Swf\Extractor\RatioDrawableInterface;
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\Structure\Record\GradientRecord;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

use function assert;
use function count;

final readonly class MorphShape
{
    public const int MAX_RATIO = RatioDrawableInterface::MAX_RATIO;

    public function __construct(
        public Rectangle $startBounds,
        public Rectangle $endBounds,

        /**
         * @var list<MorphPath>
         */
        public array $paths,
    ) {}

    /**
     * Get the interpolated shape at the given ratio
     *
     * @param int<0, 65535> $ratio
     */
    public function interpolate(int $ratio): Shape
    {
        $bounds = $this->bounds($ratio);
        $paths = [];

        foreach ($this->paths as $morphPath) {
            $paths[] = $morphPath->interpolate($ratio);
        }

        return new Shape(
            $bounds->width(),
            $bounds->height(),
            -$bounds->xmin,
            -$bounds->ymin,
            $paths,
        );
    }

    /**
     * Compute shape bounds at the given ratio
     *
     * @param int<0, 65535> $ratio
     * @return Rectangle
     */
    public function bounds(int $ratio): Rectangle
    {
        if ($ratio <= 0) {
            return $this->startBounds;
        }

        if ($ratio >= self::MAX_RATIO) {
            return $this->endBounds;
        }

        return self::interpolateRectangle($this->startBounds, $this->endBounds, $ratio);
    }

    /**
     * Apply a color transform to all paths styles in the morph shape
     * and return a new morph shape
     *
     * @param ColorTransform $colorTransform
     * @return self The new morph shape instance with transformed colors
     */
    public function transformColors(ColorTransform $colorTransform): self
    {
        $newPaths = [];

        foreach ($this->paths as $path) {
            $newPaths[] = $path->transformColors($colorTransform);
        }

        return new self(
            $this->startBounds,
            $this->endBounds,
            $newPaths,
        );
    }

    /**
     * Interpolate between two integers
     *
     * @param int $start
     * @param int $end
     * @param int<0, 65535> $ratio The interpolation ratio. 0 = start, 65535 = end
     *
     * @return int
     */
    public static function interpolateInt(int $start, int $end, int $ratio): int
    {
        return (int) (($start * (self::MAX_RATIO - $ratio) + $end * $ratio) / self::MAX_RATIO);
    }

    /**
     * Interpolate between two floats
     *
     * @param float $start
     * @param float $end
     * @param int<0, 65535> $ratio The interpolation ratio. 0 = start, 65535 = end
     *
     * @return float
     */
    public static function interpolateFloat(float $start, float $end, int $ratio): float
    {
        return ($start * (self::MAX_RATIO - $ratio) + $end * $ratio) / self::MAX_RATIO;
    }

    /**
     * Interpolate between two rectangles
     *
     * @param Rectangle $start
     * @param Rectangle $end
     * @param int<0, 65535> $ratio The interpolation ratio. 0 = start, 65535 = end
     *
     * @return Rectangle
     */
    public static function interpolateRectangle(Rectangle $start, Rectangle $end, int $ratio): Rectangle
    {
        return new Rectangle(
            self::interpolateInt($start->xmin, $end->xmin, $ratio),
            self::interpolateInt($start->xmax, $end->xmax, $ratio),
            self::interpolateInt($start->ymin, $end->ymin, $ratio),
            self::interpolateInt($start->ymax, $end->ymax, $ratio),
        );
    }

    /**
     * Interpolate between two colors
     *
     * @param Color $start
     * @param Color $end
     * @param int<0, 65535> $ratio The interpolation ratio. 0 = start, 65535 = end
     *
     * @return Color
     */
    public static function interpolateColor(Color $start, Color $end, int $ratio): Color
    {
        return new Color(
            self::interpolateInt($start->red, $end->red, $ratio),
            self::interpolateInt($start->green, $end->green, $ratio),
            self::interpolateInt($start->blue, $end->blue, $ratio),
            self::interpolateInt($start->alpha ?? 255, $end->alpha ?? 255, $ratio),
        );
    }

    /**
     * Interpolate between two matrices
     *
     * @param Matrix $start
     * @param Matrix $end
     * @param int<0, 65535> $ratio
     *
     * @return Matrix
     */
    public static function interpolateMatrix(Matrix $start, Matrix $end, int $ratio): Matrix
    {
        return new Matrix(
            self::interpolateFloat($start->scaleX, $end->scaleX, $ratio),
            self::interpolateFloat($start->scaleY, $end->scaleY, $ratio),
            self::interpolateFloat($start->rotateSkew0, $end->rotateSkew0, $ratio),
            self::interpolateFloat($start->rotateSkew1, $end->rotateSkew1, $ratio),
            self::interpolateInt($start->translateX, $end->translateX, $ratio),
            self::interpolateInt($start->translateY, $end->translateY, $ratio),
        );
    }

    /**
     * Interpolate between two gradients
     *
     * @param Gradient $start
     * @param Gradient $end
     * @param int<0, 65535> $ratio The interpolation ratio. 0 = start, 65535 = end
     *
     * @return Gradient
     */
    public static function interpolateGradient(Gradient $start, Gradient $end, int $ratio): Gradient
    {
        assert(count($start->records) === count($end->records));

        $records = [];

        foreach ($start->records as $index => $startRecord) {
            $endRecord = $end->records[$index];

            $records[] = new GradientRecord(
                self::interpolateInt($startRecord->ratio, $endRecord->ratio, $ratio),
                self::interpolateColor($startRecord->color, $endRecord->color, $ratio),
            );
        }

        return new Gradient(
            $start->spreadMode,
            $start->interpolationMode,
            $records,
            $start->focalPoint !== null && $end->focalPoint !== null ? self::interpolateFloat($start->focalPoint, $end->focalPoint, $ratio) : null,
        );
    }
}
