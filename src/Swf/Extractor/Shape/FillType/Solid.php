<?php

namespace Arakne\Swf\Extractor\Shape\FillType;

use Arakne\Swf\Parser\Structure\Record\Color;
use Override;

final readonly class Solid implements FillTypeInterface
{
    public function __construct(
        public Color $color
    ) {}

    #[Override]
    public function transformColors(array $colorTransform): static
    {
        return new self($this->color->transform($colorTransform));
    }

    #[Override]
    public function hash(): string
    {
        $color = $this->color;

        return 'S'.(($color->red << 24) | ($color->green << 16) | ($color->blue << 8) | ($color->alpha ?? 255));
    }
}
