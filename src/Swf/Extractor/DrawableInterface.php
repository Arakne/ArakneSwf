<?php

namespace Arakne\Swf\Extractor;

use Arakne\Swf\Extractor\Shape\Svg\DrawerInterface;
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
     * Draw the current character to the canvas
     *
     * @param D $drawer The drawer to use
     * @return D The passed drawer
     *
     * @template D as DrawerInterface
     */
    public function draw(DrawerInterface $drawer): DrawerInterface;

    /**
     * Transform the colors of the character
     * In case of composite characters, the transformation should be applied to all children recursively
     *
     * The current instance of the character should not be modified, a new instance should be returned
     *
     * @param ColorTransform $colorTransform
     * @return static The transformed character
     */
    public function transformColors(ColorTransform $colorTransform): static;
}
