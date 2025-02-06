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

namespace Arakne\Swf\Extractor\Sprite;

use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Dom\Element;
use Dom\XMLDocument;
use SimpleXMLElement;

use function Dom\import_simplexml;
use function sprintf;

final class SpriteDefinition
{
    public private(set) Sprite $sprite {
        get => $this->sprite ??= $this->processor->process($this->tag);
    }

    public Rectangle $bounds {
        get => $this->sprite->bounds;
    }

    public function __construct(
        private readonly SpriteProcessor $processor,

        /**
         * The character ID of the sprite
         *
         * @see SwfTagPosition::$id
         */
        public readonly int $id,

        /**
         * The raw SWF tag
         */
        public readonly DefineSpriteTag $tag,
    ) {}

    public function transformColors(array $colorTransform): self
    {
        $sprite = $this->sprite->transformColors($colorTransform);

        $self = clone $this;
        $self->sprite = $sprite;

        return $self;
    }

    public function toSvg(?string $idPrefix = null): string
    {
        $dom = XMLDocument::createEmpty();
        $svg = $dom->createElement('svg');
        $dom->append($svg);

        $svg->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        $svg->setAttribute('width', $this->bounds->width() / 20 . 'px');
        $svg->setAttribute('height', $this->bounds->height() / 20 . 'px');

        $g = $dom->createElement('g');
        $defs = $dom->createElement('defs');
        $svg->append($g, $defs);

        $g->setAttribute('transform', sprintf('matrix(1.0, 0.0, 0.0, 1.0, %f, %f)', -$this->bounds->xmin / 20, -$this->bounds->ymin / 20));

        foreach ($this->sprite->objects as $index => $object) {
            $use = $dom->createElement('use');
            $id = $object->object instanceof SpriteDefinition ? $idPrefix . 'sprite-' . $index : $idPrefix . 'shape-' . $index;
            $use->setAttribute('href', '#' . $id);
            $use->setAttribute('width', (string) ($object->object->bounds->width() / 20));
            $use->setAttribute('height', (string) ($object->object->bounds->height() / 20));

            $use->setAttribute('transform', $object->matrix->toSvgTransformation());

            $g->append($use);

            $this->processDeps($defs, $id, $object->object);
        }

        return $dom->saveXML();
    }

    private function processDeps(Element $defs, string $id, SpriteDefinition|ShapeDefinition $object): void
    {
        // Dependency already processed
        if ($defs->ownerDocument->getElementById($id)) {
            return;
        }

        $other = new SimpleXMLElement($object->toSvg($id . '-'));

        $g = $other->g;
        $g['id'] = $id;

        $defs->append($defs->ownerDocument->importNode(import_simplexml($g), true));

        foreach ($other->linearGradient as $e) {
            $defs->append($defs->ownerDocument->importNode(import_simplexml($e), true));
        }

        foreach ($other->radialGradient as $e) {
            $defs->append($defs->ownerDocument->importNode(import_simplexml($e), true));
        }

        if ($object instanceof SpriteDefinition) {
            foreach ($object->sprite->objects as $index => $object) {
                $subId = $object->object instanceof SpriteDefinition ? $id . '-' . 'sprite-' . $index : $id . '-' . 'shape-' . $index;
                $this->processDeps(
                    $defs,
                    $subId,
                    $object->object
                );
            }
        }
    }
}
