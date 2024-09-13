<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class MetadataTag
{
    public function __construct(
        public string $metadata,
    ) {
    }
}
