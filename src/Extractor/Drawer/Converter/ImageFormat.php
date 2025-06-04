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

    /**
     * Call the converter to convert the drawable to the specified format.
     *
     * @param non-negative-int $frame
     * @param array<string, string|bool> $options Additional options for the conversion
     */
    public function convert(Converter $converter, DrawableInterface $drawable, int $frame = 0, array $options = []): string
    {
        return match ($this) {
            self::Svg => $converter->toSvg($drawable, $frame),
            self::Png => $converter->toPng($drawable, $frame, $options),
            self::Jpeg => $converter->toJpeg($drawable, $frame, $options),
            self::Gif => $converter->toGif($drawable, $frame, $options),
            self::Webp => $converter->toWebp($drawable, $frame, $options),
        };
    }

    /**
     * Render the character as an animated image.
     *
     * @param Converter $converter The converter to use
     * @param DrawableInterface $drawable The drawable to render
     * @param positive-int $fps The frame rate of the animation
     * @param bool $recursive If true, will count the frames of all children recursively
     * @param array<string, string|bool> $options Additional options for the conversion
     *
     * @return string The image blob in the specified format.
     */
    public function animation(Converter $converter, DrawableInterface $drawable, int $fps, bool $recursive = false, array $options = []): string
    {
        return match ($this) {
            self::Gif => $converter->toAnimatedGif($drawable, $fps, $recursive, $options),
            self::Webp => $converter->toAnimatedWebp($drawable, $fps, $recursive, $options),
            default => throw new \RuntimeException('Animation not supported for this format'),
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
