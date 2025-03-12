<?php

namespace Arakne\Swf\Extractor\Image;

/**
 * Interface for all raster image characters defined in a SWF file.
 *
 * @todo extends DrawableInterface
 */
interface ImageCharacterInterface
{
    /**
     * Render the image as PNG.
     */
    public function toPng(): string;

    /**
     * Render the image as JPEG.
     *
     * Note: If the image has an alpha channel, it will be lost.
     */
    public function toJpeg(): string;
}
