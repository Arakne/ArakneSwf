<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class PlaceObject2Tag
{
    public function __construct(
        public bool $placeFlagMove,
        public int $depth,
        public ?int $characterId,
        public ?array $matrix,
        public ?array $colorTransform,
        public ?int $ratio,
        public ?string $name,
        public ?int $clipDepth,
        public ?array $clipActions,
    ) {
    }
}
