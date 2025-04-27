<?php

declare(strict_types=1);

namespace Arakne\Swf\Extractor\Timeline;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

/**
 * Single object displayed in a frame
 */
final readonly class FrameObject
{
    public function __construct(
        /**
         * The character id of the object
         */
        public int $characterId,

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

    /**
     * Apply color transformation to the object
     *
     * @param ColorTransform $colorTransform
     * @return self The new object with the color transformation applied
     */
    public function transformColors(ColorTransform $colorTransform): self
    {
        return new self(
            $this->characterId,
            $this->depth,
            $this->object->transformColors($colorTransform),
            $this->bounds,
            $this->matrix,
        );
    }

    /**
     * Modify the object properties and return a new object
     *
     * @param DrawableInterface|null $object
     * @param Rectangle|null $bounds
     * @param Matrix|null $matrix
     *
     * @return self
     */
    public function with(?DrawableInterface $object = null, ?Rectangle $bounds = null, ?Matrix $matrix = null): self
    {
        return new self(
            $this->characterId,
            $this->depth,
            $object ?? $this->object,
            $bounds ?? $this->bounds,
            $matrix ?? $this->matrix,
        );
    }
}
