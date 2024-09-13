<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineVideoStreamTag
{
    public function __construct(
        public int $characterId,
        public int $numFrames,
        public int $width,
        public int $height,
        public int $deblocking,
        public int $smoothing,
        public int $codecId,
    ) {
    }
}
