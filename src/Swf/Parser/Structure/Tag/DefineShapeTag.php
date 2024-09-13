<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineShapeTag
{
    public function __construct(
        public int $version,
        public int $shapeId,
        public array $shapeBounds,
        public array $shapes,
    ) {
    }
}
