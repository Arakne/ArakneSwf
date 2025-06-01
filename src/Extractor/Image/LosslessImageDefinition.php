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
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\ImageBitmapType;
use Arakne\Swf\Parser\Structure\Record\ImageDataType;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsLosslessTag;
use BadMethodCallException;
use GdImage;
use Override;
use RuntimeException;

use function assert;
use function base64_encode;
use function imagecolorallocate;
use function imagecolorallocatealpha;
use function imagesetpixel;
use function intdiv;
use function ord;
use function strlen;

/**
 * Store a raw image, extracted from a {@see DefineBitsLossless} tag.
 *
 * When the tag is in version 1, the result image has no alpha channel.
 * When the tag is in version 2, the result image has an alpha channel.
 *
 * The best export format is PNG.
 */
final class LosslessImageDefinition implements ImageCharacterInterface
{
    public readonly int $characterId;
    private ?Rectangle $bounds = null;
    private ?GD $gd = null;
    private ?string $pngData = null;

    public function __construct(
        public readonly DefineBitsLosslessTag $tag,
    ) {
        $this->characterId = $tag->characterId;
    }

    #[Override]
    public function bounds(): Rectangle
    {
        return $this->bounds ??= new Rectangle(0, $this->tag->bitmapWidth * 20, 0, $this->tag->bitmapHeight * 20);
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        return 1;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): ImageCharacterInterface
    {
        return TransformedImage::createFromGD($this->characterId, $this->bounds(), $colorTransform, clone $this->toGD());
    }

    #[Override]
    public function toBase64Data(): string
    {
        return 'data:image/png;base64,' . base64_encode($this->toPng());
    }

    #[Override]
    public function toPng(): string
    {
        return $this->pngData ??= $this->toGD()->toPng();
    }

    #[Override]
    public function toJpeg(int $quality = -1): string
    {
        return $this->toGD()->toJpeg($quality);
    }

    #[Override]
    public function toBestFormat(): ImageData
    {
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
        if ($this->gd) {
            return $this->gd;
        }

        $width = $this->tag->bitmapWidth;
        $height = $this->tag->bitmapHeight;

        if ($width < 1 || $height < 1) {
            throw new RuntimeException('Empty image is not supported');
        }

        $type = $this->tag->type();
        $gd = $type->isTrueColor() ? GD::create($width, $height) : GD::createWithColorPallet($width, $height);

        match ($type) {
            ImageBitmapType::Opaque8Bit => $this->decode8Bit($gd, $width, $height),
            ImageBitmapType::Opaque24Bit => $this->decode24Bit($gd, $width, $height),
            ImageBitmapType::Opaque15Bit => throw new BadMethodCallException('Opaque15Bit is not implemented yet'), // @todo Opaque15Bit, I don't have a sample to test
            ImageBitmapType::Transparent8Bit => $this->decode8BitWithAlpha($gd, $width, $height),
            ImageBitmapType::Transparent32Bit => $this->decode32BitWithAlpha($gd, $width, $height),
        };

        return $this->gd = $gd;
    }

    /**
     * @param GD $gd
     * @param positive-int $width
     * @param positive-int $height
     * @return void
     */
    private function decode8Bit(GD $gd, int $width, int $height): void
    {
        $colors = [];
        $colorTable = $this->tag->colorTable ?? throw new RuntimeException('Color table is missing for 8-bit image');
        $colorMapLen = strlen($colorTable);
        $image = $gd->image;

        for ($i = 0; $i < $colorMapLen; $i += 3) {
            $color = imagecolorallocate($image, ord($colorTable[$i]), ord($colorTable[$i + 1]), ord($colorTable[$i + 2]));
            assert($color !== false);
            $colors[] = $color;
        }

        $this->setColorMapPixels($image, $colors, $width, $height);
    }

    /**
     * @param GD $gd
     * @param positive-int $width
     * @param positive-int $height
     * @return void
     */
    private function decode8BitWithAlpha(GD $gd, int $width, int $height): void
    {
        $colors = [];
        $colorTable = $this->tag->colorTable ?? throw new RuntimeException('Color table is missing for 8-bit image');
        $colorMapLen = strlen($colorTable);
        $image = $gd->image;

        for ($i = 0; $i < $colorMapLen; $i += 4) {
            $color = imagecolorallocatealpha(
                $image,
                ord($colorTable[$i]),
                ord($colorTable[$i + 1]),
                ord($colorTable[$i + 2]),
                127 - (ord($colorTable[$i + 3]) >> 1), // GD alpha is 0 - 127
            );
            assert($color !== false);
            $colors[] = $color;
        }

        $this->setColorMapPixels($image, $colors, $width, $height);
    }

    /**
     * @param GdImage $gd
     * @param array<int, int> $colors
     * @param positive-int $width
     * @param positive-int $height
     * @return void
     */
    private function setColorMapPixels(GdImage $gd, array $colors, int $width, int $height): void
    {
        // Each line is 32-bit aligned, so compute the padding size added at the end of the line
        $paddingSize = (4 - ($width % 4)) & 3;

        for ($y = 0; $y < $height; ++$y) {
            $offset = $y * ($width + $paddingSize);

            for ($x = 0; $x < $width; ++$x) {
                $index = $x + $offset;
                $color = $colors[ord($this->tag->pixelData[$index])];

                imagesetpixel($gd, $x, $y, $color);
            }
        }
    }

    /**
     * @param GD $gd
     * @param positive-int $width
     * @param positive-int $height
     * @return void
     */
    private function decode32BitWithAlpha(GD $gd, int $width, int $height): void
    {
        $gd->disableAlphaBlending();

        $data = $this->tag->pixelData;
        $len = $width * $height * 4;

        for ($index = 0; $index < $len; $index += 4) {
            $pixel = $index >> 2;
            $x = $pixel % $width;
            $y = intdiv($pixel, $width);

            $alpha = ord($data[$index]);

            if ($alpha === 0) {
                // With opacity 0, the color cannot be determined, so only use fully transparent color
                /** @var non-negative-int $y */
                $gd->setTransparent($x, $y);
                continue;
            }

            $red = ord($data[$index + 1]);
            $green = ord($data[$index + 2]);
            $blue = ord($data[$index + 3]);

            /** @var non-negative-int $y */
            $gd->setPixelAlpha($x, $y, $red, $green, $blue, $alpha);
        }
    }

    private function decode24Bit(GD $gd, int $width, int $height): void
    {
        $data = $this->tag->pixelData;
        $len = $width * $height * 4;

        for ($index = 0; $index < $len; $index += 4) {
            // First byte is ignored (pixels are 32-bit aligned)
            $red = ord($data[$index + 1]);
            $green = ord($data[$index + 2]);
            $blue = ord($data[$index + 3]);

            $pixel = $index >> 2;
            $x = $pixel % $width;
            $y = intdiv($pixel, $width);

            /** @var non-negative-int $y */
            $gd->setPixel($x, $y, $red, $green, $blue);
        }
    }
}
