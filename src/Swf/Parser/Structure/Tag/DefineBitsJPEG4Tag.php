<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineBitsJPEG4Tag
{
    public function __construct(
        public int $characterId,
        public int $deblockParam,
        public string $imageData,
        public string $alphaData,
    ) {
    }
}
