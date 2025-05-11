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

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\Shape;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use LogicException;
use Override;
use SimpleXMLElement;

/**
 * Drawer implementation for generate SVG XML
 */
final class SvgCanvas implements DrawerInterface
{
    private readonly SimpleXMLElement $root;
    private readonly SvgBuilder $builder;
    private ?SimpleXMLElement $g = null;
    private ?SimpleXMLElement $defs = null;
    private int $lastId = 0;

    public function __construct(Rectangle $bounds)
    {
        $this->root = $root = new SimpleXMLElement('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $this->builder = new SvgBuilder($root);

        $root->addAttribute('width', ($bounds->width() / 20) . 'px');
        $root->addAttribute('height', ($bounds->height() / 20) . 'px');
    }

    #[Override]
    public function area(Rectangle $bounds): void
    {
        $this->g = $this->builder->addGroup($bounds);
    }

    #[Override]
    public function shape(Shape $shape): void
    {
        $this->g = $this->builder->addGroupWithOffset($shape->xOffset, $shape->yOffset);

        foreach ($shape->paths as $path) {
            $this->path($path);
        }
    }

    #[Override]
    public function image(ImageCharacterInterface $image): void
    {
        $g = $this->g = $this->builder->addGroup($image->bounds());
        $tag = $g->addChild('image');
        $tag->addAttribute('href', $image->toBase64Data());
    }

    #[Override]
    public function include(DrawableInterface $object, Matrix $matrix, int $frame = 0): void
    {
        $included = new IncludedSvgCanvas(
            $this,
            ($this->defs ??= $this->root->addChild('defs')),
        );

        $object->draw($included, $frame);

        $g = $this->g ??= $this->builder->addGroup($object->bounds());

        foreach ($included->ids as $id) {
            $use = $g->addChild('use');
            $use->addAttribute('href', '#' . $id);
            $use->addAttribute('width', (string) ($object->bounds()->width() / 20));
            $use->addAttribute('height', (string) ($object->bounds()->height() / 20));
            $use->addAttribute('transform', $matrix->toSvgTransformation());
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
        return $this->toXml();
    }

    /**
     * Generate a new object id
     *
     * @internal Should only be called by {@see IncludedSvgCanvas}
     */
    public function nextObjectId(): string
    {
        return 'object-' . $this->lastId++;
    }

    /**
     * Render the SVG as XML string
     */
    public function toXml(): string
    {
        // @phpstan-ignore return.type
        return $this->root->asXML();
    }
}
