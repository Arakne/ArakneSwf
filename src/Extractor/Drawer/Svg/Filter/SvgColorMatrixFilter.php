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

use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;

final class SvgColorMatrixFilter
{
    /**
     * Apply the color matrix effect to the given filter builder.
     *
     * @param SvgFilterBuilder $builder The filter builder to which the color matrix will be applied.
     * @param ColorMatrixFilter $filter The color matrix filter containing the parameters for the effect.
     * @param string $in The "in" attribute of SVG filter.
     *
     * @return string The result ID of the last filter. Should be used as the "in" attribute of the next filter.
     */
    public static function apply(SvgFilterBuilder $builder, ColorMatrixFilter $filter, string $in): string
    {
        $values = '';

        foreach ($filter->matrix as $i => $v) {
            if ($i % 5 === 4) {
                $v /= 255;
            }

            if ($i > 0) {
                $values .= ' ';
            }

            $values .= $v;
        }

        [$feColorMatrix, $resultId] = $builder->addResultFilter('feColorMatrix', $in);

        $feColorMatrix->addAttribute('type', 'matrix');
        $feColorMatrix->addAttribute('values', $values);

        return $resultId;
    }

}
