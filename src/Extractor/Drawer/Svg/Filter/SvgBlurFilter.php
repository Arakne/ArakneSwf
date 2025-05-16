<?php

namespace Arakne\Swf\Extractor\Drawer\Svg\Filter;

use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;

use function ceil;
use function str_repeat;
use function substr;

final class SvgBlurFilter
{
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
