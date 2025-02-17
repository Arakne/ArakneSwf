<?php

namespace Arakne\Swf\Extractor\Sprite;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

final readonly class SpriteObject
{
    public function __construct(
        /**
         * The depth of the object
         * Object with higher depth are drawn after object with lower depth (i.e. on top of them)
         */
        public int $depth,

        /**
         * The object to draw
         *
         * Note: it may differ from the original object if a color transformation is applied
         */
        public DrawableInterface $object,

        /**
         * Bound of the object, after applying the matrix
         */
        public Rectangle $bounds,

        /**
         * The transformation matrix to apply to the object
         */
        public Matrix $matrix,
    ) {}

    public function transformColors(ColorTransform $colorTransform): self
    {
        return new self(
            $this->depth,
            $this->object->transformColors($colorTransform),
            $this->bounds,
            $this->matrix,
        );
    }
}
