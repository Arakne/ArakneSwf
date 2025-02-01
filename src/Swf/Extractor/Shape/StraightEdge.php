<?php

namespace Arakne\Swf\Extractor\Shape;

use Override;

final readonly class StraightEdge implements EdgeInterface
{
    public function __construct(
        public int $fromX,
        public int $fromY,
        public int $toX,
        public int $toY,
    ) {}

    #[Override]
    public function reverse(): static
    {
        return new self($this->toX, $this->toY, $this->fromX, $this->fromY);
    }

    #[Override]
    public function matchFrom(?int $x, ?int $y): bool
    {
        return $this->fromX === $x && $this->fromY === $y;
    }

    #[Override]
    public function draw(PathDrawerInterface $drawer): void
    {
        $drawer->line($this->toX, $this->toY);
    }
}
