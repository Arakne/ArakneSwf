<?php

namespace Arakne\Swf\Extractor\Sprite;

use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

final readonly class Sprite
{
    /**
     * @var list<SpriteObject>
     */
    public array $objects;

    /**
     * @param SpriteObject ...$objects Objects to displayed, ordered by depth
     * @no-named-arguments
     */
    public function __construct(
        public Rectangle $bounds,
        SpriteObject ...$objects,
    ) {
        $this->objects = $objects;
    }

    public function transformColors(ColorTransform $colorTransform)
    {
        $objects = [];

        foreach ($this->objects as $object) {
            $objects[] = $object->transformColors($colorTransform);
        }

        return new self($this->bounds, ...$objects);
    }
}
