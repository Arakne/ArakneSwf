<?php

namespace Arakne\Swf\Parser\Structure\Action;

final readonly class GetURLData
{
    public function __construct(
        public string $url,
        public string $target,
    ) {
    }
}
