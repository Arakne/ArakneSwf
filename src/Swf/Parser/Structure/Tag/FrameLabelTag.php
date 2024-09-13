<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class FrameLabelTag
{
    public function __construct(
        public string $label,
    ) {
    }
}
