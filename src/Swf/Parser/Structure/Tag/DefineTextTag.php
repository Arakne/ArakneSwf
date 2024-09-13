<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineTextTag
{
    public function __construct(
        public int $version,
        public int $characterId,
        public array $textBounds,
        public array $textMatrix,
        public int $glyphBits,
        public int $advanceBits,
        public array $textRecords,
    ) {
    }
}
