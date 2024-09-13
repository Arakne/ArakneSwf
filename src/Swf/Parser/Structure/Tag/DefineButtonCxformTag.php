<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineButtonCxformTag
{
    public function __construct(
        public int $buttonId,
        public array $colorTransform,
    ) {
    }
}
