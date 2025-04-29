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

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\SwfTagPosition;
use Override;

/**
 * Interface for all raster image characters defined in a SWF file.
 */
interface ImageCharacterInterface extends DrawableInterface
{
    /**
     * The character id of the image in the SWF file.
     *
     * @see SwfTagPosition::$id
     */
    public int $characterId { get; }

    /**
     * Size of the image in twips.
     * Because raster images have no offset, the bounds are always (0, 0, width, height).
     */
    #[Override]
    public function bounds(): Rectangle;

    /**
     * Transform the colors of the raster image
     * A new object is returned, the current instance will not be modified.
     *
     * Note: the returned type will be different from the original type, and will directly store the transformed image.
     *
     * @param ColorTransform $colorTransform
     * @return self The transformed character
     */
    #[Override]
    public function transformColors(ColorTransform $colorTransform): self;

    /**
     * Get the URL base64 data of the image.
     *
     * The returned value will start with "data:image/png;base64," or "data:image/jpeg;base64,".
     * The image type will be determined by the best format for the current image data.
     */
    public function toBase64Data(): string;

    /**
     * Render the image as PNG.
     */
    public function toPng(): string;

    /**
     * Render the image as JPEG.
     *
     * Note: If the image has an alpha channel, it will be lost.
     *
     * @param int $quality The image quality from 0 (worst quality) to 100 (best quality). If -1 is passed, the default quality will be used.
     *                     This parameter is ignored if the image is already a JPEG.
     */
    public function toJpeg(int $quality = -1): string;
}
