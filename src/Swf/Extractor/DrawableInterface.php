<?php

namespace Arakne\Swf\Extractor;

use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

/**
 * Base type for SWF characters that can be drawn
 */
interface DrawableInterface
{
    /**
     * Size and offset of the character
     */
    public function bounds(): Rectangle;

    /**
     * Get the number of frames contained in the character
     *
     * @param bool $recursive If true, will count the frames of all children recursively
     *
     * @return positive-int
     */
    public function framesCount(bool $recursive = false): int;

    /**
     * Draw the current character to the canvas
     *
     * @param D $drawer The drawer to use
     * @param non-negative-int $frame The frame to draw. Must be greater than or equal to 0. If this value is greater than the number of frames in the character, the last frame will be used.
     * @return D The passed drawer
     *
     * @template D as DrawerInterface
     */
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface;

    /**
     * Transform the colors of the character
     * In case of composite characters, the transformation should be applied to all children recursively
     *
     * The current instance of the character should not be modified, a new instance should be returned
     *
     * @param ColorTransform $colorTransform
     * @return self The transformed character
     */
    public function transformColors(ColorTransform $colorTransform): self;
}
