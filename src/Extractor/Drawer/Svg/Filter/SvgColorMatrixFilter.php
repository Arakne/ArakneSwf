<?php

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
