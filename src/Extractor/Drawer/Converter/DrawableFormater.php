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
 * Formats a drawable to a specific image format.
 */
final class DrawableFormater
{
    private ?Converter $converter = null;

    public function __construct(
        public readonly ImageFormat $format,
        public readonly ?ImageResizerInterface $size = null,
    ) {}

    /**
     * Render the drawable to the specified image format.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param non-negative-int $frame The frame number to extract from the drawable. This value is 0-based.
     *
     * @return string The image blob in the specified format.
     */
    public function format(DrawableInterface $drawable, int $frame = 0): string
    {
        $converter = $this->converter ??= new Converter($this->size);

        return $this->format->convert($converter, $drawable, $frame);
    }

    /**
     * Get the file extension for this image format.
     */
    public function extension(): string
    {
        return $this->format->extension();
    }
}
