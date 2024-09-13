<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineBinaryDataTag
{
    public function __construct(
        public int $tag,
        public string $data,
    ) {
    }
}
