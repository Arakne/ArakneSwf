<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineScalingGridTag
{
    public function __construct(
        public int $characterId,
        public array $splitter,
    ) {
    }
}
