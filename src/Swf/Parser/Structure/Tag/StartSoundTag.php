<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class StartSoundTag
{
    public function __construct(
        public int $soundId,
        public array $soundInfo,
    ) {
    }
}
