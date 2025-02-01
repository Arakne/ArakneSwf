<?php

namespace Arakne\Swf\Extractor\Shape;

/**
 * Define the drawing style of a path
 *
 * This style is common for line and fill paths
 * This object will also be used as key to allow merging paths with the same style
 */
final readonly class PathStyle
{
    public function __construct(
        /**
         * The fill color of the current path
         * If this value is null, the path should not be filled
         */
        public ?string $fillColor = null,

        /**
         * The line color of the current path
         * If this value is null, the path should not be stroked
         */
        public ?string $lineColor = null,

        /**
         * The width of the line in twips
         *
         * This value should be divided by 20 to get the width in pixels
         * This value should be set only if the lineColor is set
         */
        public int $lineWidth = 0,
    ) {}

    /**
     * Compute the hash code of the style to be used as key
     */
    public function hash(): string
    {
        // @todo optimise ?
        return $this->fillColor . '-' . $this->lineColor . '-' . $this->lineWidth;
    }
}
