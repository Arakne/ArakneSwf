<?php

namespace Arakne\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG4Tag;

use function str_starts_with;

/**
 * Type of the image data on JPEG tags.
 *
 * @see DefineBitsJPEG2Tag
 * @see DefineBitsJPEG3Tag
 * @see DefineBitsJPEG4Tag
 */
enum ImageDataType
{
    case Jpeg;
    case Png;
    case Gif89a;

    private const string PNG_HEADER = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
    private const string GIF89A_HEADER = "GIF89a";

    /**
     * Resolve the image data type from the image data header.
     *
     * @param string $imageData
     * @return self
     */
    public static function resolve(string $imageData): self
    {
        if (str_starts_with($imageData, self::PNG_HEADER)) {
            return self::Png;
        }

        if (str_starts_with($imageData, self::GIF89A_HEADER)) {
            return self::Gif89a;
        }

        return self::Jpeg;
    }
}
