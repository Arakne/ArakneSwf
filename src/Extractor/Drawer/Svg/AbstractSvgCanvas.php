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
use Arakne\Swf\Extractor\Timeline\BlendMode;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use LogicException;
use Override;
use SimpleXMLElement;

/**
 * Base implementation for SVG canvas
 *
 * @internal
 */
abstract class AbstractSvgCanvas implements DrawerInterface
{
    /**
     * The current drawing root element.
     *
     * Will be created on first call to {@see area()}.
     * It should be unique for each canvas / drawn object.
     */
    private ?SimpleXMLElement $currentGroup = null;

    /**
     * The current target group element.
     *
     * This target depends on the current active clips, each clipping will create a new nested group.
     * If there is no active clip, this will be the same as the {@see AbstractSvgCanvas::$currentGroup}.
     *
     * If this value is null, it means that the next drawing will resolve a new target group.
     */
    private ?SimpleXMLElement $currentTarget = null;

    /**
     * The bounds of the current drawing area.
     */
    private ?Rectangle $bounds = null;

    /**
     * All active clipPath ids.
     * The key is same as the value, and is the id of the clipPath element.
     *
     * @var array<string, string>
     */
    private array $activeClipPaths = [];

    public function __construct(
        private readonly SvgBuilder $builder,
    ) {}

    #[Override]
    final public function area(Rectangle $bounds): void
    {
        $this->currentTarget = $this->currentGroup = $this->newGroup($this->builder, $bounds);
        $this->bounds = $bounds;
    }

    #[Override]
    final public function shape(Shape $shape): void
    {
        $this->currentTarget = $this->newGroupWithOffset($this->builder, $shape->xOffset, $shape->yOffset);

        foreach ($shape->paths as $path) {
            $this->path($path);
        }
    }

    #[Override]
    final public function image(ImageCharacterInterface $image): void
    {
        $g = $this->currentTarget = $this->newGroup($this->builder, $image->bounds());
        $tag = $g->addChild('image');
        $tag->addAttribute('xlink:href', $image->toBase64Data(), SvgBuilder::XLINK_NS);
    }

    #[Override]
    final public function include(DrawableInterface $object, Matrix $matrix, int $frame = 0, array $filters = [], BlendMode $blendMode = BlendMode::Normal, ?string $name = null): void
    {
        $included = new IncludedSvgCanvas($this, $this->defs());

        $object->draw($included, $frame);

        $g = $this->target($object->bounds());

        if ($filters && $included->ids) {
            $filterId = 'filter-' . $included->ids[0];
            $this->builder->addFilter($filters, $filterId);
        } else {
            $filterId = null;
        }

        foreach ($included->ids as $id) {
            $use = $g->addChild('use');

            $use->addAttribute('xlink:href', '#' . $id, SvgBuilder::XLINK_NS);
            $use->addAttribute('width', (string) ($object->bounds()->width() / 20));
            $use->addAttribute('height', (string) ($object->bounds()->height() / 20));
            $use->addAttribute('transform', $matrix->toSvgTransformation());

            if ($name) {
                $use->addAttribute('id', $name);
            }

            if ($filterId) {
                $use->addAttribute('filter', 'url(#' . $filterId . ')');
            }

            if ($cssBlendMode = $blendMode->toCssValue()) {
                $use->addAttribute('style', 'mix-blend-mode: ' . $cssBlendMode);
            }
        }
    }

    #[Override]
    final public function startClip(DrawableInterface $object, Matrix $matrix, int $frame): string
    {
        $group = $this->currentGroup ?? throw new LogicException('No group defined for clipping');
        $clipPath = $group->addChild('clipPath');
        $clipPath->addAttribute('id', $id = $this->nextObjectId());
        $clipPath->addAttribute('transform', $matrix->toSvgTransformation());

        $clipPathDrawer = new ClipPathBuilder($clipPath, $this->builder);

        $object->draw($clipPathDrawer, $frame);
        $this->activeClipPaths[$id] = $id;

        // Reset the current target, so the next drawing will apply the clip path on a new group
        $this->currentTarget = null;

        return $id;
    }

    #[Override]
    final public function endClip(string $clipId): void
    {
        unset($this->activeClipPaths[$clipId]);

        // Reset the current target, so the next drawing will apply the clip path on a new group
        $this->currentTarget = null;
    }

    #[Override]
    final public function path(Path $path): void
    {
        $g = $this->currentTarget ?? throw new LogicException('No group defined');
        $this->builder->addPath($g, $path);
    }

    private function target(Rectangle $bounds): SimpleXMLElement
    {
        $target = $this->currentTarget;

        if ($target !== null) {
            return $target;
        }

        $rootGroup = ($this->currentGroup ??= $this->newGroup($this->builder, $this->bounds ?? $bounds));

        // No clipping: use the root group
        if (!$this->activeClipPaths) {
            return $this->currentTarget = $rootGroup;
        }

        // If there are active clip paths, we need to create a new group and apply the clip paths to it
        $target = $rootGroup;

        // Rebuild current group to apply nested clip paths
        foreach ($this->activeClipPaths as $id => $_) {
            $target = $target->addChild('g');
            $target->addAttribute('clip-path', 'url(#' . $id . ')');
        }

        return $this->currentTarget = $target;
    }

    /**
     * Generate a new object id
     *
     * @internal Should only be called internally by the canvas
     */
    abstract protected function nextObjectId(): string;

    /**
     * Get the element storing the definitions of this canvas.
     */
    abstract protected function defs(): SimpleXMLElement;

    /**
     * Create a new group element with the given bounds.
     * The method {@see SvgBuilder::addGroup()} must be called by this method.
     *
     * @param SvgBuilder $builder The SVG builder to use for creating the group
     * @param Rectangle $bounds The bounds of the group to create
     *
     * @return SimpleXMLElement The new group element
     */
    abstract protected function newGroup(SvgBuilder $builder, Rectangle $bounds): SimpleXMLElement;

    /**
     * Create a new group element with the given offset.
     * The method {@see SvgBuilder::addGroupWithOffset()} must be called by this method.
     *
     * @param SvgBuilder $builder The SVG builder to use for creating the group
     * @param int $offsetX The X offset of the group to create
     * @param int $offsetY The Y offset of the group to create
     *
     * @return SimpleXMLElement The new group element
     */
    abstract protected function newGroupWithOffset(SvgBuilder $builder, int $offsetX, int $offsetY): SimpleXMLElement;
}
