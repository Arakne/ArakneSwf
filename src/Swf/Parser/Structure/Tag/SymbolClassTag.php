<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class SymbolClassTag
{
    public function __construct(
        public array $tags,
        public array $names,
    ) {
    }
}
