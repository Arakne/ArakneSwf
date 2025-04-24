<?php

namespace Arakne\Swf\Extractor\Sprite;

use Arakne\Swf\Extractor\Frame\Frame;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

use function assert;
use function count;

final readonly class Sprite
{
    /**
     * @var list<Frame>
     */
    public array $frames;

    /**
     * @param Frame ...$frames
     * @no-named-arguments
     */
    public function __construct(
        public Rectangle $bounds,
        Frame ...$frames,
    ) {
        $this->frames = $frames;
        assert(count($frames) > 0);
    }

    public function transformColors(ColorTransform $colorTransform)
    {
        $frames = [];

        foreach ($this->frames as $object) {
            $frames[] = $object->transformColors($colorTransform);
        }

        return new self($this->bounds, ...$frames);
    }
}
