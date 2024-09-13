<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class EnableDebuggerTag
{
    public function __construct(
        public int $version,
        public string $password,
    ) {
    }
}
