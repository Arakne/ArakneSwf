<?php

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\ImageDataType;

interface DefineBitsJPEGTagInterface
{
    /**
     * The stored image data type.
     */
    public ImageDataType $type { get; }

    /**
     * Raw image data.
     * Can be JPEG, PNG, or GIF89.
     *
     * Use {@see self::$type} to get the image format.
     */
    public string $imageData { get; }

    /**
     * Uncompressed alpha data as byte array.
     * Each byte is the opacity of the corresponding pixel in the {@see $imageData}.
     * The length of this array must be equal to the decoded image width * height.
     *
     * Note: this field is only present if the {@see $imageData} is a JPEG image.
     */
    public ?string $alphaData { get; }
}
