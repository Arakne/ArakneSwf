<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class ImportAssetsTag
{
    public function __construct(
        public int $version,
        public string $url,
        public array $tags,
        public array $names,
    ) {
    }
}
