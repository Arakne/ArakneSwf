<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineFontTag
{
    public function __construct(
        public int $fontId,
        /**
         * @var list<int>
         */
        public array $offsetTable,
        public array $glyphShapeData,
    ) {
    }
}
