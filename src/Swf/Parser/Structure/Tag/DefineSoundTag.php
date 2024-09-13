<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineSoundTag
{
    public function __construct(
        public int $soundId,
        public int $soundFormat,
        public int $soundRate,
        public int $soundSize,
        public int $soundType,
        public int $soundSampleCount,
        public string $soundData,
    ) {
    }
}
