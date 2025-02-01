<?php

namespace Arakne\Swf\Extractor\Shape;

/**
 * Draw a single path
 * Objects implementing this interface are stateful and should be used only once
 */
interface PathDrawerInterface
{
    /**
     * Move the cursor to the given position
     */
    public function move(int $x, int $y): void;

    /**
     * Draw a line from the current cursor position to the given position, and update the cursor position
     */
    public function line(int $toX, int $toY): void;

    /**
     * Draw a curve from the current cursor position to the given position, and update the cursor position
     */
    public function curve(int $controlX, int $controlY, int $toX, int $toY): void;
}
