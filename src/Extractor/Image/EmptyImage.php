<?php

namespace Arakne\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Image\Util\GD;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\ImageDataType;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Override;

/**
 * Fallback type for invalid or missing image.
 *
 * @internal
 */
final readonly class EmptyImage implements ImageCharacterInterface
{
    public const string PNG_DATA = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a\x00\x00\x00\x0d\x49\x48\x44\x52\x00\x00\x00\x01\x00\x00\x00\x01\x01\x00\x00\x00\x00\x37\x6e\xf9\x24\x00\x00\x00\x0a\x49\x44\x41\x54\x78\x01\x63\x60\x00\x00\x00\x02\x00\x01\x73\x75\x01\x18\x00\x00\x00\x00\x49\x45\x4e\x44\xae\x42\x60\x82";

    public function __construct(
        public int $characterId,
    ) {}

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        return 1;
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        $drawer->image($this);

        return $drawer;
    }

    #[Override]
    public function bounds(): Rectangle
    {
        // 20x20 twips = so 1x1 pixel
        static $bounds = new Rectangle(0, 20, 0, 20);

        return $bounds;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): ImageCharacterInterface
    {
        return TransformedImage::createFromPng(
            $this->characterId,
            $this->bounds(),
            $colorTransform,
            self::PNG_DATA
        );
    }

    #[Override]
    public function toBase64Data(): string
    {
        return $this->toBestFormat()->toBase64Url();
    }

    #[Override]
    public function toPng(): string
    {
        return self::PNG_DATA;
    }

    #[Override]
    public function toJpeg(int $quality = -1): string
    {
        return GD::fromPng(self::PNG_DATA)->toJpeg();
    }

    #[Override]
    public function toBestFormat(): ImageData
    {
        return new ImageData(ImageDataType::Png, self::PNG_DATA);
    }
}
