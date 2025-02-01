<?php

namespace Arakne\Swf\Extractor\Shape\Svg;

use Arakne\Swf\Extractor\Shape\PathDrawerInterface;
use Override;
use SimpleXMLElement;

final readonly class SvgPathDrawer implements PathDrawerInterface
{
    public function __construct(
        private SimpleXMLElement $element,
    ) {}

    #[Override]
    public function move(int $x, int $y): void
    {
        ($this->element)['d'] .= 'M' . ($x / 20) . ' ' . ($y / 20);
    }

    #[Override]
    public function line(int $toX, int $toY): void
    {
        ($this->element)['d'] .= 'L' . ($toX / 20) . ' ' . ($toY / 20);
    }

    #[Override]
    public function curve(int $controlX, int $controlY, int $toX, int $toY): void
    {
        ($this->element)['d'] .= 'Q' . ($controlX / 20) . ' ' . ($controlY / 20) . ' ' . ($toX / 20) . ' ' . ($toY / 20);
    }
}
