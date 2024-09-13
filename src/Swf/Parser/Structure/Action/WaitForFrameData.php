<?php

namespace Arakne\Swf\Parser\Structure\Action;

final readonly class WaitForFrameData
{
    public function __construct(
        public int $frame,
        public int $skipCount,
    ) {
    }
}
