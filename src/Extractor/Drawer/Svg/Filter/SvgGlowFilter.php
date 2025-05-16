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

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Filter\GlowFilter;
use RuntimeException;

final class SvgGlowFilter
{
    /**
     * Apply the glow effect to the given filter builder.
     *
     * @param SvgFilterBuilder $builder The filter builder to which the glow effect will be applied.
     * @param GlowFilter $filter The glow filter containing the parameters for the effect.
     * @param string $in The "in" attribute of SVG filter.
     *
     * @return string The result ID of the last filter. Should be used as the "in" attribute of the next filter.
     */
    public static function apply(SvgFilterBuilder $builder, GlowFilter $filter, string $in): string
    {
        if ($filter->innerGlow) {
            throw new RuntimeException('Not implemented: inner glow filter');
        }

        return self::outer(
            $builder,
            $filter->glowColor,
            $filter->blurX,
            $filter->blurY,
            $filter->passes,
            $filter->knockout,
            $in
        );
    }

    public static function outer(SvgFilterBuilder $builder, Color $color, float $blurX, float $blurY, int $passes, bool $knockout, string $in): string
    {
        // Create the shadow color
        [$shadowColor, $resultId] = $builder->addResultFilter('feColorMatrix', $in);

        $shadowColor->addAttribute('type', 'matrix');
        $shadowColor->addAttribute(
            'values',
            '0 0 0 0 ' . ($color->red / 255) . ' ' .
            '0 0 0 0 ' . ($color->green / 255) . ' ' .
            '0 0 0 0 ' . ($color->blue / 255) . ' ' .
            '0 0 0 ' . ($color->opacity()) . ' 0'
        );

        // Apply a blur on the shadow color
        $resultId = SvgBlurFilter::blur($builder, $blurX, $blurY, $passes, $resultId);

        // The original shape is not shown, we only show the shadow
        if ($knockout) {
            return $resultId;
        }

        // Merge the shadow with the original shape
        [$feMerge, $mergeResult] = $builder->addResultFilter('feMerge');

        $feMergeNode1 = $feMerge->addChild('feMergeNode');
        $feMergeNode1->addAttribute('in', $resultId);

        $feMergeNode2 = $feMerge->addChild('feMergeNode');
        $feMergeNode2->addAttribute('in', $in);

        return $mergeResult;
    }
}
