<?php

namespace Arakne\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Tag\DefineBitsLosslessTag;

/**
 * Define the image format of {@see DefineBitsLosslessTag}
 */
enum ImageBitmapType
{
    /**
     * 8-bit image with a color table
     *
     * Used when {@see DefineBitsLosslessTag::$bitmapFormat} is {@see DefineBitsLosslessTag::FORMAT_8_BIT}
     * and {@see DefineBitsLosslessTag::$version} is 1
     *
     * The property {@see DefineBitsLosslessTag::$colorTable} must be set
     */
    case Opaque8Bit;

    /**
     * 15-bit image (5 bits for red, 5 bits for green, 5 bits for blue)
     *
     * Used when {@see DefineBitsLosslessTag::$bitmapFormat} is {@see DefineBitsLosslessTag::FORMAT_15_BIT}
     * and {@see DefineBitsLosslessTag::$version} is 1
     *
     * The property {@see DefineBitsLosslessTag::$colorTable} is not set
     */
    case Opaque15Bit;

    /**
     * True color image without alpha channel
     *
     * Used when {@see DefineBitsLosslessTag::$bitmapFormat} is {@see DefineBitsLosslessTag::FORMAT_24_BIT}
     * and {@see DefineBitsLosslessTag::$version} is 1
     *
     * The property {@see DefineBitsLosslessTag::$colorTable} is not set
     */
    case Opaque24Bit;

    /**
     *  8-bit image with a color table supporting transparency
     *
     *  Used when {@see DefineBitsLosslessTag::$bitmapFormat} is {@see DefineBitsLosslessTag::FORMAT_8_BIT}
     *  and {@see DefineBitsLosslessTag::$version} is 2
     *
     *  The property {@see DefineBitsLosslessTag::$colorTable} must be set
     */
    case Transparent8Bit;

    /**
     * 32-bit image with alpha channel
     *
     * Used when {@see DefineBitsLosslessTag::$bitmapFormat} is {@see DefineBitsLosslessTag::FORMAT_32_BIT}
     * and {@see DefineBitsLosslessTag::$version} is 2
     *
     * The property {@see DefineBitsLosslessTag::$colorTable} is not set
     */
    case Transparent32Bit;

    public function isTrueColor(): bool
    {
        return match ($this) {
            self::Opaque24Bit, self::Transparent32Bit => true,
            default => false,
        };
    }

    /**
     * Resolve the format from a {@see DefineBitsLosslessTag}
     */
    public static function fromTag(DefineBitsLosslessTag $tag): self
    {
        return match ($tag->version) {
            1 => match ($tag->bitmapFormat) {
                DefineBitsLosslessTag::FORMAT_8_BIT => self::Opaque8Bit,
                DefineBitsLosslessTag::FORMAT_15_BIT => self::Opaque15Bit,
                DefineBitsLosslessTag::FORMAT_24_BIT => self::Opaque24Bit,
                default => throw new \UnexpectedValueException('Unknown bitmap format for version 1: ' . $tag->bitmapFormat),
            },
            2 => match ($tag->bitmapFormat) {
                DefineBitsLosslessTag::FORMAT_8_BIT => self::Transparent8Bit,
                DefineBitsLosslessTag::FORMAT_32_BIT => self::Transparent32Bit,
                default => throw new \UnexpectedValueException('Unknown bitmap format for version 2: ' . $tag->bitmapFormat),
            },
            default => throw new \UnexpectedValueException('Unknown version: ' . $tag->version),
        };
    }
}
