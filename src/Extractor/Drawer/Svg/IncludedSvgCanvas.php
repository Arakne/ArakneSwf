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
use BadMethodCallException;
use Override;
use SimpleXMLElement;

/**
 * Drawer for SVG dependencies
 *
 * @internal Should only be used by {@see SvgCanvas}
 */
final class IncludedSvgCanvas extends AbstractSvgCanvas
{
    /**
     * List of ids of objects drawn in this canvas
     * Each id should be referenced with a <use> tag
     *
     * @var list<string>
     */
    public private(set) array $ids = [];

    /**
     * @param AbstractSvgCanvas $root The root canvas
     * @param SimpleXMLElement $defs The <defs> element of the root canvas
     * @param bool $subpixelStrokeWidth Enable subpixel stroke width
     */
    public function __construct(
        private readonly AbstractSvgCanvas $root,
        private readonly SimpleXMLElement $defs,
        bool $subpixelStrokeWidth = false,
    ) {
        parent::__construct(new SvgBuilder($this->defs, $subpixelStrokeWidth));
    }

    #[Override]
    public function render(): string
    {
        throw new BadMethodCallException('This is an internal implementation, rendering is performed by the root canvas');
    }

    #[Override]
    protected function nextObjectId(): string
    {
        return $this->root->nextObjectId();
    }

    #[Override]
    protected function defs(): SimpleXMLElement
    {
        return $this->defs;
    }

    #[Override]
    protected function newGroup(SvgBuilder $builder, Rectangle $bounds): SimpleXMLElement
    {
        $group = $builder->addGroup($bounds);
        $group->addAttribute('id', $this->ids[] = $this->nextObjectId());

        return $group;
    }

    #[Override]
    protected function newGroupWithOffset(SvgBuilder $builder, int $offsetX, int $offsetY): SimpleXMLElement
    {
        $group = $builder->addGroupWithOffset($offsetX, $offsetY);
        $group->addAttribute('id', $this->ids[] = $this->nextObjectId());

        return $group;
    }
}
