<?php

namespace Arakne\Swf\Extractor\Shape;

/**
 * Represents a single edge of a shape
 */
interface EdgeInterface
{
    /**
     * The X coordinate of the starting point
     * This value is in twips (1/20th of a pixel)
     */
    public int $fromX { get; }

    /**
     * The Y coordinate of the starting point
     * This value is in twips (1/20th of a pixel)
     */
    public int $fromY { get; }

    /**
     * The X coordinate of the ending point
     * This value is in twips (1/20th of a pixel)
     */
    public int $toX { get; }

    /**
     * The Y coordinate of the ending point
     * This value is in twips (1/20th of a pixel)
     */
    public int $toY { get; }

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
