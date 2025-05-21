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

use InvalidArgumentException;

use Override;

use function count;
use function escapeshellarg;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function substr;
use function trim;

/**
 * Parse the SVG string using inkscape to render as PNG, before passing it to Imagick.
 */
final readonly class InkscapeImagickSvgRenderer extends AbstractCommandImagickSvgRenderer
{
    public function __construct(string $command = 'inkscape')
    {
        parent::__construct($command);
    }

    #[Override]
    protected function buildCommand(string $command, string $backgroundColor): string
    {
        [$backgroundColor, $backgroundOpacity] = $this->parseBackgroundColor($backgroundColor);

        return sprintf('%s  --pipe --export-type=png -b %s -y %f', $command, escapeshellarg($backgroundColor), $backgroundOpacity);
    }

    /**
     * @param string $color The background color in CSS format
     * @return list{string, float} Get the hex color and opacity from the background color.
     */
    private function parseBackgroundColor(string $color): array
    {
        if ($color === '' || $color === 'none' || $color === 'transparent') {
            return ['#000000', 0.0];
        }

        if ($color[0] === '#') {
            return [$color, 1.0];
        }

        $color = strtolower($color);

        if (str_starts_with($color, 'rgba(')) {
            $color = substr($color, 5, -1);
            $parts = explode(',', $color, 4);

            if (count($parts) !== 4) {
                throw new InvalidArgumentException('Invalid RGBA color format');
            }

            $r = (int) trim($parts[0]);
            $g = (int) trim($parts[1]);
            $b = (int) trim($parts[2]);
            $a = (float) trim($parts[3]);

            return [sprintf('#%02x%02x%02x', $r, $g, $b), $a];
        }

        if (str_starts_with($color, 'rgb(')) {
            $color = substr($color, 4, -1);
            $parts = explode(',', $color, 3);

            if (count($parts) !== 3) {
                throw new InvalidArgumentException('Invalid RGB color format');
            }

            $r = (int) trim($parts[0]);
            $g = (int) trim($parts[1]);
            $b = (int) trim($parts[2]);

            return [sprintf('#%02x%02x%02x', $r, $g, $b), 1.0];
        }

        return [$color, 1.0];
    }
}
