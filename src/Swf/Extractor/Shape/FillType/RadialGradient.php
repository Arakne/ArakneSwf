<?php

namespace Arakne\Swf\Extractor\Shape\FillType;

use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Override;

use function hash;
use function json_encode;

final readonly class RadialGradient implements FillTypeInterface
{
    public function __construct(
        public Matrix $matrix,
        public Gradient $gradient,
    ) {}

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        return new self(
            $this->matrix,
            $this->gradient->transformColors($colorTransform),
        );
    }

    #[Override]
    public function hash(): string
    {
        return 'R' . hash('xxh128', json_encode($this));
    }
}
