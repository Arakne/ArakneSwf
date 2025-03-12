<?php

namespace Arakne\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Image\Util\GD;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsTag;
use Arakne\Swf\Parser\Structure\Tag\JPEGTablesTag;
use Override;

/**
 * Store a raw image, extracted from a DefineBits tag.
 * Unlike {@see JpegImageDefinition}, this class only handle JPEG images, and requires {@see JPEGTablesTag} to be present.
 */
final class ImageBitsDefinition implements ImageCharacterInterface
{
    public function __construct(
        public readonly DefineBitsTag $tag,
        public readonly JPEGTablesTag $jpegTables,
    ) {}

    #[Override]
    public function toPng(): string
    {
        return GD::fromJpeg($this->jpegTables->data . $this->tag->imageData)->toPng();
    }

    #[Override]
    public function toJpeg(): string
    {
        return GD::fixJpegData($this->jpegTables->data . $this->tag->imageData);
    }
}
