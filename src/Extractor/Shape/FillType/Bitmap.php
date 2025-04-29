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

namespace Arakne\Swf\Extractor\Shape\FillType;

use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Image\TransformedImage;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Override;

use function crc32;

final readonly class Bitmap implements FillTypeInterface
{
    private string $hash;

    public function __construct(
        public ImageCharacterInterface $bitmap,
        public Matrix $matrix,
        public bool $smoothed = true,
        public bool $repeat = false,
    ) {
        $this->hash = self::computeHash($bitmap, $matrix, $smoothed, $repeat);
    }

    #[Override]
    public function hash(): string
    {
        return $this->hash;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        return new self(
            $this->bitmap->transformColors($colorTransform),
            $this->matrix,
        );
    }

    private static function computeHash(ImageCharacterInterface $bitmap, Matrix $matrix, bool $smoothed, bool $repeat): string
    {
        $imgHash = $bitmap->characterId;

        // When a color transform is applied, make sure that the hash is different
        if ($bitmap instanceof TransformedImage) {
            $imgHash .= '-' . crc32($bitmap->toPng());
        }

        $prefix = ($repeat ? 'R' : 'C') .'B';

        if (!$smoothed) {
            $prefix .= 'N';
        }

        return $prefix.$imgHash.'-'.crc32($matrix->toSvgTransformation());
    }
}
