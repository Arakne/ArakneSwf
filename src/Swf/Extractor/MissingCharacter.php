<?php

namespace Arakne\Swf\Extractor;

use Arakne\Swf\Extractor\Shape\Svg\DrawerInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Override;

/**
 * Type for nonexistent character
 */
final readonly class MissingCharacter implements DrawableInterface
{
    public function __construct(
        /**
         * The character ID of the requested character
         *
         * @see SwfTagPosition::$id
         */
        public int $id,
    ) {}

    #[Override]
    public function bounds(): Rectangle
    {
        return new Rectangle(0, 0, 0, 0);
    }

    #[Override]
    public function draw(DrawerInterface $drawer): DrawerInterface
    {
        return $drawer;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        return $this;
    }
}