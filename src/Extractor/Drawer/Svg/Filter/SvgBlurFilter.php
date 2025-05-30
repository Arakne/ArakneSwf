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

namespace Arakne\Swf\Extractor\Drawer\Svg\Filter;

use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;

use function ceil;
use function str_repeat;
use function substr;

final class SvgBlurFilter
{
    // Limit the box blur radius to avoid crashes or performance issues.
    // The limit is set to 9 because RSVG handle only 20x20 pixels for the convolution kernel,
    // so 9 is the maximum radius that can be used without exceeding this limit.
    public const int MAX_BOX_BLUR_RADIUS = 9;

    // Use sqrt(3) which approximates the blur box variance
    public const float BLUR_BOX_RADIUS_TO_GAUSSIAN_BLUR_RATIO = 1.732;

    /**
     * Apply the blur effect to the given filter builder.
     *
     * @param SvgFilterBuilder $builder The filter builder to which the blur effect will be applied.
     * @param BlurFilter $filter The blur filter containing the parameters for the effect.
     * @param string $in The "in" attribute of SVG filter.
     *
     * @return string
     */
    public static function apply(SvgFilterBuilder $builder, BlurFilter $filter, string $in): string
    {
        return self::blur($builder, $filter->blurX, $filter->blurY, $filter->passes, $in);
    }

    /**
     * Create filters for the blur effect similar to the one in Flash.
     * Flash does not use a Gaussian blur, but a box blur, so <feConvolveMatrix> is used instead of <feGaussianBlur>.
     *
     * @param SvgFilterBuilder $builder
     * @param float $blurX Blur radius in the X direction. Only the integer part is used.
     * @param float $blurY Blur radius in the Y direction. Only the integer part is used.
     * @param int $passes Number of passes to apply the blur. 3 passes should approximate a Gaussian blur.
     * @param string $in The "in" attribute of SVG filter.
     *
     * @return string The result ID of the last filter. Should be used as the "in" attribute of the next filter.
     */
    public static function blur(SvgFilterBuilder $builder, float $blurX, float $blurY, int $passes, string $in): string
    {
        if ($blurX > self::MAX_BOX_BLUR_RADIUS || $blurY > self::MAX_BOX_BLUR_RADIUS) {
            // The blur box is too large to use a convolution filter, so we use a Gaussian blur to approximate it.
            $stdDevX = $blurX / self::BLUR_BOX_RADIUS_TO_GAUSSIAN_BLUR_RATIO;
            $stdDevY = $blurY / self::BLUR_BOX_RADIUS_TO_GAUSSIAN_BLUR_RATIO;

            $builder->addOffset($stdDevX * 3, $stdDevY * 3);

            [$feGaussianBlur, $result] = $builder->addResultFilter('feGaussianBlur', $in);
            $feGaussianBlur->addAttribute('stdDeviation', $stdDevX . ' ' . $stdDevY);

            return $result;
        }

        $blurX = (int) (2 * ceil($blurX) + 1);
        $blurY = (int) (2 * ceil($blurY) + 1);

        $order = $blurX . ' ' . $blurY;
        $divisor = $blurX * $blurY;
        $kernelMatrix = substr(str_repeat(' 1', $divisor), 1);
        $lastResult = $in;

        for ($i = 0; $i < $passes; ++$i) {
            [$feConvolveMatrix, $lastResult] = $builder->addResultFilter('feConvolveMatrix', $lastResult);

            $feConvolveMatrix->addAttribute('order', $order);
            $feConvolveMatrix->addAttribute('divisor', (string) $divisor);
            $feConvolveMatrix->addAttribute('kernelMatrix', $kernelMatrix);
        }

        return $lastResult;
    }
}
