<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineBitsJPEG2Tag
{
    public function __construct(
        public int $characterId,
        public string $imageData,
    ) {
    }
}
