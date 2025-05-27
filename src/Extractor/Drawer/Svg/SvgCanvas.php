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

use function array_key_first;
use function array_keys;
use function count;
use function implode;
use function var_dump;

/**
 * Drawer implementation for generate SVG XML
 *
 * @todo refactor with IncludedSvgCanvas
 */
final class SvgCanvas implements DrawerInterface
{
    private readonly SimpleXMLElement $root;
    private readonly SvgBuilder $builder;
    private ?SimpleXMLElement $g = null;
    private ?SimpleXMLElement $currentTarget = null;
    private ?Rectangle $bounds = null;
    private ?SimpleXMLElement $defs = null;
    private int $lastId = 0;

    /**
     * All active clipPath ids.
     * The key is same as the value, and is the id of the clipPath element.
     *
     * @var array<string, string>
     */
    private array $activeClipPaths = [];

    public function __construct(Rectangle $bounds)
    {
        $this->root = $root = new SimpleXMLElement('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"></svg>');
        $this->builder = new SvgBuilder($root);

        $root->addAttribute('width', ($bounds->width() / 20) . 'px');
        $root->addAttribute('height', ($bounds->height() / 20) . 'px');
    }

    #[Override]
    public function area(Rectangle $bounds): void
    {
        $this->currentTarget = $this->g = $this->builder->addGroup($bounds);
        $this->bounds = $bounds;
    }

    #[Override]
    public function shape(Shape $shape): void
    {
        $this->currentTarget = $this->builder->addGroupWithOffset($shape->xOffset, $shape->yOffset);

        foreach ($shape->paths as $path) {
            $this->path($path);
        }
    }

    #[Override]
    public function image(ImageCharacterInterface $image): void
    {
        $g = $this->currentTarget = $this->builder->addGroup($image->bounds());
        $tag = $g->addChild('image');
        $tag->addAttribute('xlink:href', $image->toBase64Data(), SvgBuilder::XLINK_NS);
    }

    #[Override]
    public function include(DrawableInterface $object, Matrix $matrix, int $frame = 0, array $filters = [], BlendMode $blendMode = BlendMode::Normal): void
    {
        $included = new IncludedSvgCanvas(
            $this,
            ($this->defs ??= $this->root->addChild('defs')),
        );

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

            if ($filterId) {
                $use->addAttribute('filter', 'url(#' . $filterId . ')');
            }

            if ($cssBlendMode = $blendMode->toCssValue()) {
                $use->addAttribute('style', 'mix-blend-mode: ' . $cssBlendMode);
            }
        }
    }

    private function target(Rectangle $bounds): SimpleXMLElement
    {
        $target = $this->currentTarget;

        if ($target !== null) {
            return $target;
        }

        $rootGroup = ($this->g ??= $this->builder->addGroup($this->bounds ?? $bounds));

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

    #[Override]
    public function startClip(DrawableInterface $object, Matrix $matrix, int $frame): string
    {
        $group = $this->g ?? throw new LogicException('No group defined for clipping');
        $clipPath = $group->addChild('clipPath'); // @todo handle g null
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
    public function endClip(string $clipId): void
    {
        unset($this->activeClipPaths[$clipId]);

        // Reset the current target, so the next drawing will apply the clip path on a new group
        $this->currentTarget = null;
    }

    #[Override]
    public function path(Path $path): void
    {
        $g = $this->currentTarget ?? throw new LogicException('No group defined');
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
