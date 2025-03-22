<?php

namespace Arakne\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Image\Util\GD;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\ImageDataType;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG3Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG4Tag;
use BadMethodCallException;

use Override;

use function base64_encode;
use function getimagesizefromstring;
use function ord;

/**
 * Store a raw image, extracted from a DefineBitsJPEG tag.
 *
 * Note: The raw image data can be a JPEG, PNG, or GIF89 and not necessarily a JPEG.
 *       Also, an alpha channel can be present in the case of a JPEG image, so the extracted image is generally a PNG.
 */
final class JpegImageDefinition implements ImageCharacterInterface
{
    public readonly int $characterId;
    private ?Rectangle $bounds = null;

    public function __construct(
        public readonly DefineBitsJPEG2Tag|DefineBitsJPEG3Tag|DefineBitsJPEG4Tag $tag,
    ) {
        $this->characterId = $tag->characterId;
    }

    #[Override]
    public function bounds(): Rectangle
    {
        if ($this->bounds) {
            return $this->bounds;
        }

        $data = $this->tag->imageData;

        if ($this->tag->type === ImageDataType::Jpeg) {
            $data = GD::fixJpegData($data);
        }

        [$width, $height] = getimagesizefromstring($data);

        return $this->bounds = new Rectangle(0, $width * 20, 0, $height * 20);
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): ImageCharacterInterface
    {
        if ($this->tag->type === ImageDataType::Jpeg && !isset($this->tag->alphaData)) {
            return TransformedImage::createFromJpeg($this->characterId, $this->bounds(), $colorTransform, $this->tag->imageData);
        }

        return TransformedImage::createFromGD($this->characterId, $this->bounds(), $colorTransform, $this->toGD());
    }

    #[Override]
    public function toBase64Data(): string
    {
        if ($this->tag->type === ImageDataType::Jpeg && !isset($this->tag->alphaData)) {
            return 'data:image/jpeg;base64,' . base64_encode(GD::fixJpegData($this->tag->imageData));
        }

        return 'data:image/png;base64,' . base64_encode($this->toPng());
    }

    #[Override]
    public function toPng(): string
    {
        if ($this->tag->type === ImageDataType::Png) {
            return $this->tag->imageData;
        }

        return $this->toGD()->toPng();
    }

    #[Override]
    public function toJpeg(int $quality = -1): string
    {
        if ($this->tag->type === ImageDataType::Jpeg && !isset($this->tag->alphaData)) {
            return GD::fixJpegData($this->tag->imageData);
        }

        return $this->toGD()->toJpeg($quality);
    }

    private function toGD(): GD
    {
        // @todo handle v4
        return match ($this->tag->type) {
            ImageDataType::Png => GD::fromPng($this->tag->imageData),
            ImageDataType::Gif89a => $this->parseGifData(),
            ImageDataType::Jpeg => $this->parseJpegData(),
        };
    }

    private function parseGifData(): GD
    {
        throw new BadMethodCallException('Not implemented');
    }

    private function parseJpegData(): GD
    {
        $gd = GD::fromJpeg($this->tag->imageData);

        if ($alphaData = ($this->tag->alphaData ?? null)) {
            $this->applyAlphaChannel($gd, $alphaData);
        }

        return $gd;
    }

    private function applyAlphaChannel(GD $gd, string $alphaData): void
    {
        $gd->disableAlphaBlending();

        $with = $gd->width;
        $height = $gd->height;

        for ($y = 0; $y < $height; $y++) {
            $offset = $y * $with;

            for ($x = 0; $x < $with; $x++) {
                $alpha = ord($alphaData[$x + $offset]);

                if ($alpha === 0) {
                    $gd->setTransparent($x, $y);
                    continue;
                }

                $color = $gd->color($x, $y);

                $red = ($color >> 16) & 0xFF;
                $green = ($color >> 8) & 0xFF;
                $blue = $color & 0xFF;

                $gd->setPixelAlpha($x, $y, $red, $green, $blue, $alpha);
            }
        }
    }
}
