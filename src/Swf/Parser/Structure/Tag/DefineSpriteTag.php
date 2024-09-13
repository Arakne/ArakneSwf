<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineSpriteTag
{
    public function __construct(
        public int $spriteId,
        public int $frameCount,
        /**
         * @var list<object>
         */
        public array $tags,
    ) {
    }
}
