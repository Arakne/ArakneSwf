<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineBitsTag
{
    public function __construct(
        public int $characterId,
        public string $data,
    ) {
    }
}
