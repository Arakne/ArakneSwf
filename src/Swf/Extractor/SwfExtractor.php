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

namespace Arakne\Swf\Extractor;

use Arakne\Swf\Extractor\Image\ImageBitsDefinition;
use Arakne\Swf\Extractor\Image\JpegImageDefinition;
use Arakne\Swf\Extractor\Image\LosslessImageDefinition;
use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\Shape\ShapeProcessor;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\Sprite\SpriteProcessor;
use Arakne\Swf\Parser\Structure\SwfTagPosition;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG3Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsLosslessTag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsTag;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Arakne\Swf\Parser\Structure\Tag\JPEGTablesTag;
use Arakne\Swf\SwfFile;

/**
 * Extract resources from a SWF file
 */
final class SwfExtractor
{
    private ?array $characters = null;

    public function __construct(
        private readonly SwfFile $file,
    ) {}

    /**
     * Extract all shapes from the SWF file.
     *
     * The result array will be indexed by the character ID (i.e. {@see SwfTagPosition::$id}).
     *
     * Note: Shape will not be processed immediately, but only when requested.
     *
     * @return array<string, ShapeDefinition>
     */
    public function shapes(): array
    {
        // @todo cache
        $shapes = [];
        $processor = new ShapeProcessor($this);

        foreach ($this->file->tags(DefineShapeTag::TYPE_V1, DefineShapeTag::TYPE_V2, DefineShapeTag::TYPE_V3, DefineShape4Tag::TYPE_V4) as $pos => $tag) {
            if (($id = $pos->id) === null) {
                continue;
            }

            $shapes[$id] = new ShapeDefinition($processor, $id, $tag);
        }

        return $shapes;
    }

    /**
     * Extract all raster images from the SWF file.
     *
     * The result array will be indexed by the character ID (i.e. {@see SwfTagPosition::$id}).
     *
     * @return array<int, LosslessImageDefinition|JpegImageDefinition|ImageBitsDefinition>
     */
    public function images(): array
    {
        return $this->extractLosslessImages()
            + $this->extractJpeg()
            + $this->extractDefineBits()
        ;
    }

    /**
     * @return array<string, SpriteDefinition>
     */
    public function sprites(): array
    {
        // @todo cache
        $sprites = [];
        $processor = new SpriteProcessor($this);

        foreach ($this->file->tags(DefineSpriteTag::TYPE) as $pos => $tag) {
            if (($id = $pos->id) === null) {
                continue;
            }

            $sprites[$id] = new SpriteDefinition($processor, $id, $tag);
        }

        return $sprites;
    }

    public function character(int $characterId): ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition
    {
        $this->characters ??= ($this->shapes() + $this->sprites() + $this->images());

        return $this->characters[$characterId] ?? new MissingCharacter($characterId);
    }

    /**
     * @return array<int, LosslessImageDefinition>
     */
    private function extractLosslessImages(): array
    {
        $images = [];

        foreach ($this->file->tags(DefineBitsLosslessTag::V1_ID, DefineBitsLosslessTag::V2_ID) as $pos => $tag) {
            if (($id = $pos->id) === null) {
                continue;
            }

            $images[$id] = new LosslessImageDefinition($tag);
        }

        return $images;
    }

    /**
     * @return array<int, ImageBitsDefinition>
     */
    private function extractDefineBits(): array
    {
        $images = [];
        $jpegTables = null;

        /** @var JPEGTablesTag|DefineBitsTag $tag */
        foreach ($this->file->tags(JPEGTablesTag::ID, DefineBitsTag::ID) as $tag) {
            if ($tag instanceof JPEGTablesTag) {
                $jpegTables = $tag;
                continue;
            }

            $images[$tag->characterId] = new ImageBitsDefinition($tag, $jpegTables);
        }

        return $images;
    }

    /**
     * @return array<int, JpegImageDefinition>
     */
    private function extractJpeg(): array
    {
        $images = [];

        foreach ($this->file->tags(DefineBitsJPEG2Tag::ID, DefineBitsJPEG3Tag::ID, DefineBitsJPEG4Tag::ID) as $pos => $tag) {
            if (($id = $pos->id) === null) {
                continue;
            }

            $images[$id] = new JpegImageDefinition($tag);
        }

        return $images;
    }
}
