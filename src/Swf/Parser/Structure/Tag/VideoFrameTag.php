<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class VideoFrameTag
{
    public function __construct(
        public int $streamId,
        public int $frameNum,
        public string $videoData,
    ) {
    }
}
