<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineFontAlignZonesTag
{
    public function __construct(
        public int $fontId,
        public int $csmTableHint,
        public array $zoneTable,
    ) {
    }
}
