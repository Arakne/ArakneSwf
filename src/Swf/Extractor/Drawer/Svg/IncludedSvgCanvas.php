<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Arakne-Swf: derived from SWF.php
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Extractor\Drawer\Svg;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use BadMethodCallException;
use LogicException;
use Override;
use SimpleXMLElement;

/**
 * Drawer for SVG dependencies
 *
 * @internal Should only be used by {@see SvgCanvas}
 */
final class IncludedSvgCanvas implements DrawerInterface
{
    /**
     * List of ids of objects drawn in this canvas
     * Each id should be referenced with a <use> tag
     *
     * @var list<string>
     */
    public private(set) array $ids = [];
    private readonly SvgBuilder $builder;
    private ?SimpleXMLElement $g = null;

    /**
     * @param SvgCanvas $root The root canvas
     * @param SimpleXMLElement $defs The <defs> element of the root canvas
     */
    public function __construct(
        private readonly SvgCanvas $root,
        private readonly SimpleXMLElement $defs
    ) {
        $this->builder = new SvgBuilder($defs);
    }

    #[Override]
    public function area(Rectangle $bounds): void
    {
        $this->g = $this->builder->addGroup($bounds);
        $this->g['id'] = $this->ids[] = $this->root->nextObjectId();
    }

    #[Override]
    public function shape(Shape $shape): void
    {
        $this->g = $this->builder->addGroupWithOffset($shape->xOffset, $shape->yOffset);
        $this->g['id'] = $this->ids[] = $this->root->nextObjectId();

        foreach ($shape->paths as $path) {
            $this->path($path);
        }
    }

    #[Override]
    public function image(ImageCharacterInterface $image): void
    {
        $g = $this->g = $this->builder->addGroup($image->bounds());
        $tag = $g->addChild('image');
        $tag['href'] = $image->toBase64Data();
    }

    #[Override]
    public function include(DrawableInterface $object, Matrix $matrix): void
    {
        $included = new IncludedSvgCanvas($this->root, $this->defs);

        $object->draw($included);
        $bounds = $object->bounds();

        $g = $this->g;

        if (!$g) {
            $g = $this->builder->addGroup($object->bounds());
            $g['id'] = $this->ids[] = $this->root->nextObjectId();
        }

        foreach ($included->ids as $id) {
            $use = $g->addChild('use');
            $use['href'] = '#' . $id;
            $use['width'] = $bounds->width() / 20;
            $use['height'] = $bounds->height() / 20;
            $use['transform'] = $matrix->toSvgTransformation();
        }
    }

    #[Override]
    public function path(Path $path): void
    {
        $g = $this->g ?? throw new LogicException('No group defined');
        $this->builder->addPath($g, $path);
    }

    #[Override]
    public function render(): string
    {
        throw new BadMethodCallException('This is an internal implementation, rendering is performed by the root canvas');
    }
}
