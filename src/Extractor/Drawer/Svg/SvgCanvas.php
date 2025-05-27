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

use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Override;
use SimpleXMLElement;

/**
 * Drawer implementation for generate SVG XML
 */
final class SvgCanvas extends AbstractSvgCanvas
{
    private readonly SimpleXMLElement $root;
    private ?SimpleXMLElement $defs = null;
    private int $lastId = 0;

    public function __construct(Rectangle $bounds)
    {
        $this->root = $root = new SimpleXMLElement('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"></svg>');
        $root->addAttribute('width', ($bounds->width() / 20) . 'px');
        $root->addAttribute('height', ($bounds->height() / 20) . 'px');

        parent::__construct(new SvgBuilder($root));
    }

    #[Override]
    public function render(): string
    {
        return $this->toXml();
    }

    /**
     * Render the SVG as XML string
     */
    public function toXml(): string
    {
        // @phpstan-ignore return.type
        return $this->root->asXML();
    }

    #[Override]
    protected function nextObjectId(): string
    {
        return 'object-' . $this->lastId++;
    }

    #[Override]
    protected function defs(): SimpleXMLElement
    {
        return $this->defs ??= $this->root->addChild('defs');
    }

    #[Override]
    protected function newGroup(SvgBuilder $builder, Rectangle $bounds): SimpleXMLElement
    {
        return $builder->addGroup($bounds);
    }

    #[Override]
    protected function newGroupWithOffset(SvgBuilder $builder, int $offsetX, int $offsetY): SimpleXMLElement
    {
        return $builder->addGroupWithOffset($offsetX, $offsetY);
    }
}
