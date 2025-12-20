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
use Arakne\Swf\Parser\Structure\Tag\DefineBitsTag;
use Arakne\Swf\Parser\Structure\Tag\JPEGTablesTag;
use Override;
use RuntimeException;

use function base64_encode;
use function getimagesizefromstring;

/**
 * Store a raw image, extracted from a DefineBits tag.
 * Unlike {@see JpegImageDefinition}, this class only handle JPEG images, and requires {@see JPEGTablesTag} to be present.
 */
final class ImageBitsDefinition implements ImageCharacterInterface
{
    public readonly int $characterId;
    private ?Rectangle $bounds = null;
    private ?string $fixedJpegData = null;

    public function __construct(
        public readonly DefineBitsTag $tag,
        public readonly JPEGTablesTag $jpegTables,
    ) {
        $this->characterId = $tag->characterId;
    }

    #[Override]
    public function bounds(): Rectangle
    {
        if ($this->bounds) {
            return $this->bounds;
        }

        [$width, $height] = getimagesizefromstring($this->toJpeg()) ?: throw new RuntimeException('Invalid JPEG data');

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
        return TransformedImage::createFromJpeg($this->characterId, $this->bounds(), $colorTransform, $this->toJpeg());
    }

    #[Override]
    public function modify(CharacterModifierInterface $modifier, int $maxDepth = -1): ImageCharacterInterface
    {
        return $modifier->applyOnImage($this);
    }

    #[Override]
    public function toBase64Data(): string
    {
        return 'data:image/jpeg;base64,' . base64_encode($this->toJpeg());
    }

    #[Override]
    public function toPng(): string
    {
        return GD::fromJpeg($this->jpegTables->data . $this->tag->imageData)->toPng();
    }

    #[Override]
    public function toJpeg(int $quality = -1): string
    {
        return $this->fixedJpegData ??= GD::fixJpegData($this->jpegTables->data . $this->tag->imageData);
    }

    #[Override]
    public function toBestFormat(): ImageData
    {
        return new ImageData(ImageDataType::Jpeg, $this->toJpeg());
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        $drawer->image($this);

        return $drawer;
    }
}
