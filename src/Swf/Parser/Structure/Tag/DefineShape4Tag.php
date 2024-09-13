<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineShape4Tag
{
    public function __construct(
        public int $shapeId,
        public array $shapeBounds,
        public array $edgeBounds,
        public int $reserved,
        public bool $usesFillWindingRule,
        public bool $usesNonScalingStrokes,
        public bool $usesScalingStrokes,
        public array $shapes,
    ) {
    }
}
