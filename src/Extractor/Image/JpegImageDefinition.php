<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Extractor\Image;

use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Image\Util\GD;
use Arakne\Swf\Extractor\Modifier\CharacterModifierInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\ImageDataType;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG3Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG4Tag;
use BadMethodCallException;
use Override;
use RuntimeException;
use WeakMap;

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
    private ?GD $gd = null;
    private ?string $pngData = null;

    /**
     * Cache last transformed image with its color transform.
     * WeakMap is used to avoid memory leaks.
     *
     * @var WeakMap<TransformedImage, ColorTransform>|null
     */
    private ?WeakMap $colorTransformCache = null;

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

        [$width, $height] = getimagesizefromstring($data) ?: throw new RuntimeException('Invalid JPEG data');

        return $this->bounds = new Rectangle(0, $width * 20, 0, $height * 20);
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        return 1;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): ImageCharacterInterface
    {
        /**
         * @var WeakMap<TransformedImage, ColorTransform> $cache
         * @phpstan-ignore assign.propertyType
         */
        $cache = $this->colorTransformCache ??= new WeakMap();

        foreach ($cache as $image => $otherTransform) {
            if ($otherTransform == $colorTransform) {
                return $image;
            }
        }

        if ($this->tag->type === ImageDataType::Jpeg && !isset($this->tag->alphaData)) {
            $transformed = TransformedImage::createFromJpeg($this->characterId, $this->bounds(), $colorTransform, $this->tag->imageData);
        } else {
            $transformed = TransformedImage::createFromPng($this->characterId, $this->bounds(), $colorTransform, $this->toPng());
        }

        $cache[$transformed] = $colorTransform;

        return $transformed;
    }

    #[Override]
    public function modify(CharacterModifierInterface $modifier, int $maxDepth = -1): ImageCharacterInterface
    {
        return $modifier->applyOnImage($this);
    }

    #[Override]
    public function toBase64Data(): string
    {
        return $this->toBestFormat()->toBase64Url();
    }

    #[Override]
    public function toPng(): string
    {
        if ($this->tag->type === ImageDataType::Png) {
            return $this->tag->imageData;
        }

        return $this->pngData ??= $this->toGD()->toPng();
    }

    #[Override]
    public function toJpeg(int $quality = -1): string
    {
        if ($this->tag->type === ImageDataType::Jpeg && !isset($this->tag->alphaData)) {
            return GD::fixJpegData($this->tag->imageData);
        }

        return $this->toGD()->toJpeg($quality);
    }

    #[Override]
    public function toBestFormat(): ImageData
    {
        if ($this->tag->type === ImageDataType::Jpeg && !isset($this->tag->alphaData)) {
            return new ImageData(ImageDataType::Jpeg, GD::fixJpegData($this->tag->imageData));
        }

        return new ImageData(ImageDataType::Png, $this->toPng());
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        $drawer->image($this);

        return $drawer;
    }

    private function toGD(): GD
    {
        // @todo handle deblockParam on v4 tag
        return $this->gd ??= match ($this->tag->type) {
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
