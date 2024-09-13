<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class StartSound2Tag
{
    public function __construct(
        public string $soundClassName,
        public array $soundInfo,
    ) {
    }
}
