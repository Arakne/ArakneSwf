<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class JPEGTablesTag
{
    public function __construct(
        public string $data,
    ) {
    }
}
