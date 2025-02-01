<?php

namespace Arakne\Swf\Extractor\Shape;

use Override;

final readonly class CurvedEdge implements EdgeInterface
{
    public function __construct(
        public int $fromX,
        public int $fromY,
        public int $controlX,
        public int $controlY,
        public int $toX,
        public int $toY,
    ) {}

    #[Override]
    public function reverse(): static
    {
        return new self($this->toX, $this->toY, $this->controlX, $this->controlY, $this->fromX, $this->fromY);
    }

    #[Override]
    public function matchFrom(?int $x, ?int $y): bool
    {
        return $this->fromX === $x && $this->fromY === $y;
    }

    #[Override]
    public function draw(PathDrawerInterface $drawer): void
    {
        $drawer->curve($this->controlX, $this->controlY, $this->toX, $this->toY);
    }
}
