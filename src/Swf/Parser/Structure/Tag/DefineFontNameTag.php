<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineFontNameTag
{
    public function __construct(
        public int $fontId,
        public string $fontName,
        public string $fontCopyright,
    ) {
    }
}
