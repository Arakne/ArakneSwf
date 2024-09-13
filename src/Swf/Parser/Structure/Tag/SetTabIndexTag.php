<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class SetTabIndexTag
{
    public function __construct(
        public int $depth,
        public int $tabIndex,
    ) {
    }
}
