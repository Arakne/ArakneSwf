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
 * Copyright (C) 2025 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Extractor\Drawer\Converter;

use Arakne\Swf\Extractor\DrawableInterface;

/**
 * Enum of supported image formats.
 */
enum ImageFormat: string
{
    case Svg = 'svg';
    case Png = 'png';
    case Jpeg = 'jpeg';
    case Gif = 'gif';
    case Webp = 'webp';
    // @todo animated formats

    /**
     * Call the converter to convert the drawable to the specified format.
     *
     * @param non-negative-int $frame
     */
    public function convert(Converter $converter, DrawableInterface $drawable, int $frame = 0): string
    {
        return match ($this) {
            self::Svg => $converter->toSvg($drawable, $frame),
            self::Png => $converter->toPng($drawable, $frame),
            self::Jpeg => $converter->toJpeg($drawable, $frame),
            self::Gif => $converter->toGif($drawable, $frame),
            self::Webp => $converter->toWebp($drawable, $frame),
        };
    }

    /**
     * Get the file extension for this image format.
     */
    public function extension(): string
    {
        return $this->value;
    }
}
