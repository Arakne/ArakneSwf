<?php

namespace Arakne\Swf\Extractor\Drawer\Svg;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Extractor\Timeline\BlendMode;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Override;
use SimpleXMLElement;

/**
 * Builder for <clipPath> SVG element.
 * Only shapes are supported, all other methods are ignored.
 */
final readonly class ClipPathBuilder implements DrawerInterface
{
    public function __construct(
        private SimpleXMLElement $clipPath,
        private SvgBuilder $builder,
    ) {}

    #[Override]
    public function area(Rectangle $bounds): void {}

    #[Override]
    public function shape(Shape $shape): void
    {
        foreach ($shape->paths as $path) {
            $element = $this->builder->addPath($this->clipPath, $path);
            $element->addAttribute('transform', 'translate(' . $shape->xOffset / 20 . ',' . $shape->yOffset / 20 . ')');
        }
    }

    #[Override]
    public function image(ImageCharacterInterface $image): void {}

    #[Override]
    public function include(DrawableInterface $object, Matrix $matrix, int $frame = 0, array $filters = [], BlendMode $blendMode = BlendMode::Normal, ?string $name = null): void {}

    #[Override]
    public function startClip(DrawableInterface $object, Matrix $matrix, int $frame): string
    {
        return '';
    }

    #[Override]
    public function endClip(string $clipId): void {}

    #[Override]
    public function path(Path $path): void {}

    #[Override]
    public function render(): null
    {
        return null;
    }
}
