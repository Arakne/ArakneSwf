<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineMorphShapeTag
{
    public function __construct(
        public int $characterId,
        public array $startBounds,
        public array $endBounds,
        public int $offset,
        public array $fillStyles,
        public array $lineStyles,
        public array $startEdges,
        public array $endEdges,
    ) {
    }
}
