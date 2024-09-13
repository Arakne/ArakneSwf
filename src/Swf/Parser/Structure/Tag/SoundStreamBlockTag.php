<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class SoundStreamBlockTag
{
    public function __construct(
        public string $soundData,
    ) {
    }
}
