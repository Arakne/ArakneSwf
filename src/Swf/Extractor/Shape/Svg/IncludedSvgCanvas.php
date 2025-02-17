<?php

namespace Arakne\Swf\Extractor\Shape\Svg;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Shape\FillType\LinearGradient;
use Arakne\Swf\Extractor\Shape\FillType\RadialGradient;
use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use BadMethodCallException;
use InvalidArgumentException;
use Override;
use SimpleXMLElement;

use function sprintf;

// @todo interface
// @todo move to new package
// @todo improve side effects
final class IncludedSvgCanvas implements DrawerInterface
{
    public readonly string $id;
    private readonly SvgCanvas $root;
    private readonly SimpleXMLElement $defs;
    private ?SimpleXMLElement $g = null;

    /**
     * @param SimpleXMLElement $defs
     */
    public function __construct(string $id, SvgCanvas $root, SimpleXMLElement $defs)
    {
        $this->id = $id;
        $this->root = $root;
        $this->defs = $defs;
    }

    #[Override]
    public function bounds(Rectangle $bounds): void
    {
        $g = $this->g();
        $g['transform'] = sprintf(
            'matrix(1, 0, 0, 1, %h, %h)',
            -$bounds->xmin / 20,
            -$bounds->ymin / 20,
        );
    }

    #[Override]
    public function shape(Shape $shape): void
    {
        $g = $this->g();

        $g['transform'] = sprintf(
            'matrix(1.0, 0.0, 0.0, 1, %h, %h)',
            $shape->xOffset / 20,
            $shape->yOffset / 20,
        );

        foreach ($shape->paths as $path) {
            $this->path($path);
        }
    }

    #[Override]
    public function include(DrawableInterface $object, Matrix $matrix): void
    {
        $included = new IncludedSvgCanvas(
            'object-' . $this->root->lastId++,
            $this->root,
            $this->defs,
        );

        $object->draw($included);
        $bounds = $object->bounds();

        $use = $this->g()->addChild('use');
        $use['href'] = '#'. $included->id;
        $use['width'] = $bounds->width() / 20;
        $use['height'] = $bounds->height() / 20;
        $use['transform'] = $matrix->toSvgTransformation();
    }

    #[Override]
    public function path(Path $path): void
    {
        $g = $this->g();
        $pathElement = $g->addChild('path');

        $this->applyFillStyle($this->defs, $pathElement, $path->style->fill);
        $pathElement['stroke'] = $path->style->lineColor?->hex() ?? 'none';

        if ($path->style->lineColor?->hasTransparency() === true) {
            $pathElement['stroke-opacity'] = $path->style->lineColor->opacity();
        }

        if ($path->style->lineWidth > 0) {
            $pathElement['stroke-width'] = $path->style->lineWidth / 20;
            $pathElement['stroke-linecap'] = 'round';
            $pathElement['stroke-linejoin'] = 'round';
        }

        $path->draw(new SvgPathDrawer($pathElement));
    }

    #[Override]
    public function render(): string
    {
        throw new BadMethodCallException('This is an internal implementation, rendering is performed by the root canvas');
    }

    private function g(): SimpleXMLElement
    {
        if ($this->g) {
            return $this->g;
        }

        $this->g = $this->defs->addChild('g');
        $this->g['id'] = $this->id;

        return $this->g;
    }

    private function applyFillStyle(SimpleXMLElement $svg, SimpleXMLElement $path, Solid|LinearGradient|RadialGradient|null $style): void
    {
        if ($style === null) {
            $path['fill'] = 'none';
            return;
        }

        $path['fill-rule'] = 'evenodd';

        match (true) {
            $style instanceof Solid => $this->applyFillSolid($path, $style),
            $style instanceof LinearGradient => $this->applyFillLinearGradient($svg, $path, $style),
            $style instanceof RadialGradient => $this->applyFillRadialGradient($svg, $path, $style),
            default => throw new InvalidArgumentException('Unknown fill style: '.$style::class),
        };
    }

    private function applyFillSolid(SimpleXMLElement $path, Solid $style): void
    {
        $path['fill'] = $style->color->hex();

        if ($style->color->hasTransparency()) {
            $path['fill-opacity'] = $style->color->opacity();
        }
    }

    private function applyFillLinearGradient(SimpleXMLElement $svg, SimpleXMLElement $path, LinearGradient $style)
    {
        $linearGradient = $svg->addChild('linearGradient');

        $linearGradient['gradientTransform'] = $style->matrix->toSvgTransformation();
        $linearGradient['gradientUnits'] = 'userSpaceOnUse';
        $linearGradient['spreadMethod'] = 'pad';
        $linearGradient['id'] = 'gradient-'.$style->hash();

        // All gradients are defined in a standard space called the gradient square. The gradient square is centered at (0,0),
        // and extends from (-16384,-16384) to (16384,16384).
        $linearGradient['x1'] = '-819.2';
        $linearGradient['x2'] = '819.2';

        foreach ($style->gradient->records as $record) {
            $stop = $linearGradient->addChild('stop');
            $stop['offset'] = $record->ratio / 255;
            $stop['stop-color'] = $record->color->hex();
            $stop['stop-opacity'] = $record->color->opacity();
        }

        $path['fill'] = 'url(#'.$linearGradient['id'].')';
    }

    private function applyFillRadialGradient(SimpleXMLElement $svg, SimpleXMLElement $path, RadialGradient $style)
    {
        $radialGradient = $svg->addChild('radialGradient');

        $radialGradient['gradientTransform'] = $style->matrix->toSvgTransformation();
        $radialGradient['gradientUnits'] = 'userSpaceOnUse';
        $radialGradient['spreadMethod'] = 'pad';
        $radialGradient['id'] = 'gradient-'.$style->hash();

        // All gradients are defined in a standard space called the gradient square. The gradient square is centered at (0,0),
        // and extends from (-16384,-16384) to (16384,16384).
        $radialGradient['cx'] = '0';
        $radialGradient['cy'] = '0';
        $radialGradient['r'] = '819.2';

        foreach ($style->gradient->records as $record) {
            $stop = $radialGradient->addChild('stop');
            $stop['offset'] = $record->ratio / 255;
            $stop['stop-color'] = $record->color->hex();

            if ($record->color->hasTransparency()) {
                $stop['stop-opacity'] = $record->color->opacity();
            }
        }

        $path['fill'] = 'url(#'.$radialGradient['id'].')';
    }
}
