<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class PlaceObject3Tag
{
    public function __construct(
        public bool $move,
        public bool $hasImage,
        public int $depth,
        public ?string $className,
        public ?int $characterId,
        public ?array $matrix,
        public ?array $colorTransform,
        public ?int $ratio,
        public ?string $name,
        public ?int $clipDepth,
        public ?array $surfaceFilterList,
        public ?int $blendMode,
        public ?int $bitmapCache,
        public ?array $clipActions,
    ) {
    }
}
