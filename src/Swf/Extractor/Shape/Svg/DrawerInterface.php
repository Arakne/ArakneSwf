<?php

namespace Arakne\Swf\Extractor\Shape\Svg;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;

// @todo move to dedicated package
interface DrawerInterface
{
    /**
     * Define the bounds of the current drawing
     *
     * Note: this method will replace the current bounds
     *
     * @param Rectangle $bounds
     */
    public function bounds(Rectangle $bounds): void;

    /**
     * Draw a new shape
     *
     * @param Shape $shape
     */
    public function shape(Shape $shape): void;

    /**
     * Include a sprite or shape in the current drawing
     *
     * @todo id parameter
     *
     * @param DrawableInterface $object
     * @param Matrix $matrix
     */
    public function include(DrawableInterface $object, Matrix $matrix): void;

    /**
     * Draw a path
     *
     * @param Path $path
     */
    public function path(Path $path): void;

    /**
     * Render the drawing
     * The returned value depends on the implementation
     */
    public function render(): mixed;
}
