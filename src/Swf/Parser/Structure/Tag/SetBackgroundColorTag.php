<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class SetBackgroundColorTag
{
    public function __construct(
        public int $red,
        public int $green,
        public int $blue,
    ) {
    }
}
