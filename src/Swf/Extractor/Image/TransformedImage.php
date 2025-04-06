<?php

namespace Arakne\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Image\Util\GD;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Override;

use function base64_encode;

/**
 * Image character with applied color transform.
 *
 * @internal
 */
final readonly class TransformedImage implements ImageCharacterInterface
{
    private function __construct(
        public int $characterId,
        private Rectangle $bounds,
        private string $pngData,
    ) {}

    #[Override]
    public function bounds(): Rectangle
    {
        return $this->bounds;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): ImageCharacterInterface
    {
        return self::createFromPng($this->characterId, $this->bounds, $colorTransform, $this->pngData);
    }

    #[Override]
    public function toBase64Data(): string
    {
        return 'data:image/png;base64,' . base64_encode($this->pngData);
    }

    #[Override]
    public function toPng(): string
    {
        return $this->pngData;
    }

    #[Override]
    public function toJpeg(int $quality = -1): string
    {
        return GD::fromPng($this->pngData)->toJpeg($quality);
    }

    #[Override]
    public function draw(DrawerInterface $drawer): DrawerInterface
    {
        $drawer->image($this);

        return $drawer;
    }

    /**
     * Apply the color transform the PNG data and return a new instance.
     *
     * @param int $characterId The original character ID {@see ImageCharacterInterface::$characterId}
     * @param Rectangle $bounds The original bounds {@see ImageCharacterInterface::bounds()}
     * @param ColorTransform $colorTransform The color transform to apply
     * @param string $pngData The PNG data to transform
     *
     * @return self
     */
    public static function createFromPng(int $characterId, Rectangle $bounds, ColorTransform $colorTransform, string $pngData): self
    {
        return self::createFromGD($characterId, $bounds, $colorTransform, GD::fromPng($pngData));
    }

    /**
     * Apply the color transform the JPEG data and return a new instance.
     *
     * @param int $characterId The original character ID {@see ImageCharacterInterface::$characterId}
     * @param Rectangle $bounds The original bounds {@see ImageCharacterInterface::bounds()}
     * @param ColorTransform $colorTransform The color transform to apply
     * @param string $jpegData The JPEG data to transform
     *
     * @return self
     */
    public static function createFromJpeg(int $characterId, Rectangle $bounds, ColorTransform $colorTransform, string $jpegData): self
    {
        return self::createFromGD($characterId, $bounds, $colorTransform, GD::fromJpeg($jpegData));
    }

    /**
     * Apply the color transform on the parsed GD image and return a new instance.
     *
     * Note: the GD image is modified in place, so be sure to clone it if you need to keep the original.
     *
     * @param int $characterId The original character ID {@see ImageCharacterInterface::$characterId}
     * @param Rectangle $bounds The original bounds {@see ImageCharacterInterface::bounds()}
     * @param ColorTransform $colorTransform The color transform to apply
     * @param GD $image The GD image to transform
     *
     * @return self
     */
    public static function createFromGD(int $characterId, Rectangle $bounds, ColorTransform $colorTransform, GD $image): self
    {
        $image->transformColors($colorTransform);
        $pngData = $image->toPng();

        return new self($characterId, $bounds, $pngData);
    }
}
