<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineMorphShape2Tag
{
    public function __construct(
        public int $characterId,
        public array $startBounds,
        public array $endBounds,
        public array $startEdgeBounds,
        public array $endEdgeBounds,
        public bool $usesNonScalingStrokes,
        public bool $usesScalingStrokes,
        public int $offset,
        public array $fillStyles,
        public array $lineStyles,
        public array $startEdges,
        public array $endEdges,
    ) {
    }
}
