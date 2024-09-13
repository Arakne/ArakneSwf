<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class PlaceObjectTag
{
    public function __construct(
        public int $characterId,
        public int $depth,
        public array $matrix,
        public ?array $colorTransform,
    ) {
    }
}
