<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineBitsLosslessTag
{
    public function __construct(
        public int $version,
        public int $characterId,
        public int $bitmapFormat,
        public int $bitmapWidth,
        public int $bitmapHeight,
        public ?string $colorTable,
        public string $pixelData,
    ) {
    }
}
