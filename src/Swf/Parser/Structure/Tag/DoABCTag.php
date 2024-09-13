<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DoABCTag
{
    public function __construct(
        public int $flags,
        public string $name,
        public string $data,
    ) {
    }
}
