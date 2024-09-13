<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class ExportAssetsTag
{
    public function __construct(
        public array $tags,
        public array $names,
    ) {
    }
}
