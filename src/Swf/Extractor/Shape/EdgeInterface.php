<?php

namespace Arakne\Swf\Extractor\Shape;

/**
 * Represents a single edge of a shape
 */
interface EdgeInterface
{
    /**
     * Reverse the edge and return the new instance
     */
    public function reverse(): static;

    /**
     * Check if the from point match the given coordinates
     */
    public function matchFrom(?int $x, ?int $y): bool;

    /**
     * Draw the current edge on the given drawer
     */
    public function draw(PathDrawerInterface $drawer): void;
}
