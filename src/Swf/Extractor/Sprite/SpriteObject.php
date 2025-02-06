<?php

namespace Arakne\Swf\Extractor\Sprite;

use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

use function var_dump;

final readonly class SpriteObject
{
    public function __construct(
        public int $depth,
        public ShapeDefinition|SpriteDefinition $object,
        public Rectangle $bounds,
        public Matrix $matrix,
    ) {}

    public function transformColors(array $colorTransform)
    {
        return new self(
            $this->depth,
            $this->object->transformColors($colorTransform),
            $this->bounds,
            $this->matrix,
        );
    }
}
