<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineBitsJPEG3Tag
{
    public function __construct(
        public int $characterId,
        public string $imageData,
        public string $alphaData,
    ) {
    }
}
