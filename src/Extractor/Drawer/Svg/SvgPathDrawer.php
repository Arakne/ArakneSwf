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

namespace Arakne\Swf\Extractor\Drawer\Svg;

use Arakne\Swf\Extractor\Shape\PathDrawerInterface;
use Override;
use SimpleXMLElement;

/**
 * Draw a path tag in a SVG element
 * This class will only fill the "d" attribute of the element
 *
 * @internal This class is not intended to be used outside of the library
 */
final class SvgPathDrawer implements PathDrawerInterface
{
    private string $d = '';

    public function __construct(
        private readonly SimpleXMLElement $element,
    ) {}

    #[Override]
    public function move(int $x, int $y): void
    {
        $this->d .= 'M' . ($x / 20) . ' ' . ($y / 20);
    }

    #[Override]
    public function line(int $toX, int $toY): void
    {
        $this->d .= 'L' . ($toX / 20) . ' ' . ($toY / 20);
    }

    #[Override]
    public function curve(int $controlX, int $controlY, int $toX, int $toY): void
    {
        $this->d .= 'Q' . ($controlX / 20) . ' ' . ($controlY / 20) . ' ' . ($toX / 20) . ' ' . ($toY / 20);
    }

    #[Override]
    public function draw(): void
    {
        $this->element->addAttribute('d', $this->d);
        $this->d = '';
    }
}
