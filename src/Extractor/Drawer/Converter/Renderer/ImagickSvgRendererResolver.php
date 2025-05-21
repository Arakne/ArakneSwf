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

namespace Arakne\Swf\Extractor\Drawer\Converter\Renderer;

use RuntimeException;

final class ImagickSvgRendererResolver
{
    /**
     * @return list<class-string<ImagickSvgRendererInterface>>
     */
    private const array IMPLEMENTATIONS = [
        NativeImagickSvgRenderer::class,
        RsvgImagickSvgRenderer::class,
        InkscapeImagickSvgRenderer::class,
    ];

    private static ?ImagickSvgRendererInterface $instance = null;

    /**
     * Get the SVG renderer available on the system.
     */
    public static function get(): ImagickSvgRendererInterface
    {
        if (self::$instance) {
            return self::$instance;
        }

        foreach (self::IMPLEMENTATIONS as $className) {
            $instance = new $className();

            if ($instance->supported()) {
                return self::$instance = $instance;
            }
        }

        throw new RuntimeException('No supported SVG renderer found. Please install Inkscape, rsvg-convert or enable the RSVG delegate in Imagick.');
    }
}
