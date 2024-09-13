<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class CSMTextSettingsTag
{
    public function __construct(
        public int $textId,
        public int $useFlashType,
        public int $gridFit,
        public float $thickness,
        public float $sharpness,
    ) {
    }
}
