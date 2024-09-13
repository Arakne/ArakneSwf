<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineFont4Tag
{
    public function __construct(
        public int $fontId,
        public bool $italic,
        public bool $bold,
        public string $name,
        public ?string $data,
    ) {
    }
}
