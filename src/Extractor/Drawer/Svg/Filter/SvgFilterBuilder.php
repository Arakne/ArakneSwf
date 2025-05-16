<?php

namespace Arakne\Swf\Extractor\Drawer\Svg\Filter;

use Arakne\Swf\Parser\Structure\Record\Filter\BevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ConvolutionFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\DropShadowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GlowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientBevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientGlowFilter;
use SimpleXMLElement;

/**
 * Builds SVG filters for Flash filters.
 *
 * This class is stateful, and directly modifies the SVG filter element.
 * So it must be recreated for each filter.
 */
final class SvgFilterBuilder
{
    private int $filterCount = 0;
    private string $lastResult = 'SourceGraphic';

    public function __construct(
        private readonly SimpleXMLElement $filter,
    ) {}

    /**
     * Apply a new filter to the current filter builder.
     */
    public function apply(DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter $filter): void
    {
        $this->lastResult = match (true) {
            $filter instanceof ColorMatrixFilter => SvgColorMatrixFilter::apply($this, $filter, $this->lastResult),
            $filter instanceof BlurFilter => SvgBlurFilter::apply($this, $filter, $this->lastResult),
            $filter instanceof GlowFilter => SvgGlowFilter::apply($this, $filter, $this->lastResult),
            default => throw new \RuntimeException('Unsupported filter type: ' . $filter::class),
        };
    }

    /**
     * Create a new filter element.
     *
     * @param string $element The name of the filter element to create.
     * @param string|null $in The "in" attribute of the filter element. If null, no attribute is added.
     *
     * @return SimpleXMLElement
     */
    public function addFilter(string $element, ?string $in = null): SimpleXMLElement
    {
        $filterElement = $this->filter->addChild($element);

        if ($in) {
            $filterElement->addAttribute('in', $in);
        }

        return $filterElement;
    }

    /**
     * Create a new filter element and add a result attribute to it.
     *
     * @param string $element The name of the filter element to create.
     * @param string|null $in The "in" attribute of the filter element. If null, no attribute is added.
     *
     * @return list{SimpleXMLElement, string} The filter element and the result ID
     */
    public function addResultFilter(string $element, ?string $in = null): array
    {
        $filterElement = $this->addFilter($element, $in);
        $filterElement->addAttribute('result', $result = 'filter' . ++$this->filterCount);

        $filterElement->addAttribute('id', $result);

        return [$filterElement, $result];
    }

    /**
     * Create a new filter builder, and its corresponding filter element.
     *
     * @param SimpleXMLElement $root The root element to which the filter will be added.
     * @param string $id The ID of the filter element to create.
     *
     * @return self
     */
    public static function create(SimpleXMLElement $root, string $id): self
    {
        $filter = $root->addChild('filter');
        $filter->addAttribute('id', $id);

        return new self($filter);
    }
}
