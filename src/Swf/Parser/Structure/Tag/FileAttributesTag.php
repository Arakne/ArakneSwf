<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class FileAttributesTag
{
    public function __construct(
        public bool $useDirectBlit,
        public bool $useGpu,
        public bool $hasMetadata,
        public bool $actionScript3,
        public bool $useNetwork,
    ) {
    }
}
