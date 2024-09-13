<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineButtonSoundTag
{
    public function __construct(
        public int $buttonId,
        public int $buttonSoundChar0,
        public ?array $buttonSoundInfo0,
        public int $buttonSoundChar1,
        public ?array $buttonSoundInfo1,
        public int $buttonSoundChar2,
        public ?array $buttonSoundInfo2,
        public int $buttonSoundChar3,
        public ?array $buttonSoundInfo3,
    ) {
    }
}
