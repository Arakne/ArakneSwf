<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class RemoveObjectTag
{
    public function __construct(
        public int $characterId,
        public int $depth,
    ) {
    }
}
