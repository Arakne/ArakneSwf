<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class SoundStreamHeadTag
{
    public function __construct(
        public int $version,
        public int $playbackSoundRate,
        public int $playbackSoundSize,
        public int $playbackSoundType,
        public int $streamSoundCompression,
        public int $streamSoundRate,
        public int $streamSoundSize,
        public int $streamSoundType,
        public int $streamSoundSampleCount,
        public ?int $latencySeek,
    ) {
    }
}
