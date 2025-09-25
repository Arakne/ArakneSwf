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

namespace Arakne\Swf\Extractor;

use Arakne\Swf\Extractor\Image\ImageBitsDefinition;
use Arakne\Swf\Extractor\Image\JpegImageDefinition;
use Arakne\Swf\Extractor\Image\LosslessImageDefinition;
use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\Shape\ShapeProcessor;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\Timeline\Timeline;
use Arakne\Swf\Extractor\Timeline\TimelineProcessor;
use Arakne\Swf\Parser\Error\ParserExceptionInterface;
use Arakne\Swf\Parser\Structure\SwfTag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG3Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsLosslessTag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsTag;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Arakne\Swf\Parser\Structure\Tag\ExportAssetsTag;
use Arakne\Swf\Parser\Structure\Tag\JPEGTablesTag;
use Arakne\Swf\Parser\Structure\Tag\SymbolClassTag;
use Arakne\Swf\SwfFile;
use Arakne\Swf\Util\Memory;
use InvalidArgumentException;

use function array_flip;
use function assert;
use function sprintf;

/**
 * Extract resources from a SWF file
 */
final class SwfExtractor
{
    /**
     * @var array<int, ShapeDefinition|SpriteDefinition|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition>|null
     */
    private ?array $characters = null;

    /**
     * @var array<int, ShapeDefinition>|null
     */
    private ?array $shapes = null;

    /**
     * @var array<int, SpriteDefinition>|null
     */
    private ?array $sprites = null;

    /**
     * @var array<int, LosslessImageDefinition|JpegImageDefinition|ImageBitsDefinition>|null
     */
    private ?array $images = null;

    /**
     * Exported asset name to character ID.
     *
     * @var array<array-key, int>|null
     */
    private ?array $exported = null;
    private ?Timeline $timeline = null;

    public function __construct(
        private readonly SwfFile $file,
    ) {}

    /**
     * Check if the given error is enabled.
     *
     * @param int $error The error code. This should be one of the {@see SwfFile::ERROR_*} constants.
     *
     * @return bool
     */
    public function errorEnabled(int $error): bool
    {
        return ($this->file->errors & $error) !== 0;
    }

    /**
     * Extract all shapes from the SWF file.
     *
     * The result array will be indexed by the character ID (i.e. {@see SwfTag::$id}).
     *
     * Note: Shape will not be processed immediately, but only when requested.
     *
     * @return array<int, ShapeDefinition>
     * @throws ParserExceptionInterface
     */
    public function shapes(): array
    {
        $shapes = $this->shapes;

        if ($shapes !== null) {
            return $shapes;
        }

        $shapes = [];
        $processor = new ShapeProcessor($this);

        foreach ($this->file->tags(DefineShapeTag::TYPE_V1, DefineShapeTag::TYPE_V2, DefineShapeTag::TYPE_V3, DefineShape4Tag::TYPE_V4) as $pos => $tag) {
            assert($tag instanceof DefineShapeTag || $tag instanceof DefineShape4Tag);

            if (($id = $pos->id) === null) {
                continue;
            }


            $shapes[$id] = new ShapeDefinition($processor, $id, $tag);
        }

        return $this->shapes = $shapes;
    }

    /**
     * Extract all raster images from the SWF file.
     *
     * The result array will be indexed by the character ID (i.e. {@see SwfTag::$id}).
     *
     * @return array<int, LosslessImageDefinition|JpegImageDefinition|ImageBitsDefinition>
     * @throws ParserExceptionInterface
     */
    public function images(): array
    {
        return $this->images ??= $this->extractLosslessImages()
            + $this->extractJpeg()
            + $this->extractDefineBits()
        ;
    }

    /**
     * @return array<int, SpriteDefinition>
     * @throws ParserExceptionInterface
     */
    public function sprites(): array
    {
        $sprites = $this->sprites;

        if ($sprites !== null) {
            return $sprites;
        }

        $sprites = [];
        $processor = new TimelineProcessor($this);

        foreach ($this->file->tags(DefineSpriteTag::TYPE) as $pos => $tag) {
            assert($tag instanceof DefineSpriteTag);

            if (($id = $pos->id) === null) {
                continue;
            }

            $sprites[$id] = new SpriteDefinition($processor, $id, $tag);
        }

        return $this->sprites = $sprites;
    }

    /**
     * Get the root swf file timeline animation.
     *
     * @param bool $useFileDisplayBounds If true, the timeline will be adjusted to the file display bounds (i.e. {@see SwfFile::displayBounds()}). If false, the bounds will be the highest bounds of all frames.
     *
     * @return Timeline
     * @throws ParserExceptionInterface
     */
    public function timeline(bool $useFileDisplayBounds = true): Timeline
    {
        $timeline = $this->timeline;

        if ($timeline === null) {
            $processor = new TimelineProcessor($this);

            $this->timeline = $timeline = $processor->process($this->file->tags(...TimelineProcessor::TAG_TYPES));
        }

        if (!$useFileDisplayBounds) {
            return $timeline;
        }

        return $timeline->withBounds($this->file->displayBounds());
    }

    /**
     * Get a SWF character by its ID.
     * When the character ID is not found, a {@see MissingCharacter} will be returned.
     *
     * @param int $characterId
     *
     * @return ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition
     * @throws ParserExceptionInterface
     */
    public function character(int $characterId): ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition
    {
        $this->characters ??= ($this->shapes() + $this->sprites() + $this->images());

        return $this->characters[$characterId] ?? new MissingCharacter($characterId);
    }

    /**
     * Get a character by its exported name.
     *
     * @throws InvalidArgumentException If the given name is not exported.
     * @throws ParserExceptionInterface
     *
     * @see SwfExtractor::exported() to get the list of exported names.
     */
    public function byName(string $name): ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition
    {
        $id = $this->exported()[$name] ?? null;

        if ($id === null) {
            throw new InvalidArgumentException(sprintf('The name "%s" has not been exported', $name));
        }

        return $this->character($id);
    }

    /**
     * Get all exported tag names to character ID.
     *
     * Note: Due to the way PHP handles array keys, numeric keys will be converted to integers.
     *
     * @return array<array-key, int>
     * @throws ParserExceptionInterface
     */
    public function exported(): array
    {
        if ($this->exported !== null) {
            return $this->exported;
        }

        $exported = [];

        foreach ($this->file->tags(ExportAssetsTag::ID, SymbolClassTag::TYPE) as $tag) {
            if ($tag instanceof ExportAssetsTag) {
                $exported += array_flip($tag->characters);
            }
            elseif ($tag instanceof SymbolClassTag) {
                $exported += array_flip($tag->symbols);
            }
        }

        return $this->exported = $exported;
    }

    /**
     * Release all loaded resources.
     *
     * This method allows to free memory, and break some circular references.
     * It should be called when the extractor is no longer needed to help the garbage collector.
     * The extractor can be used again after this method is called, but it will need to re-load all resources.
     */
    public function release(): void
    {
        $this->characters = null;
        $this->sprites = null;
        $this->images = null;
        $this->shapes = null;
        $this->exported = null;
        $this->timeline = null;
    }

    /**
     * Release resources if the memory usage is above the given limit.
     *
     * This method will call {@see SwfExtractor::release()} if the current memory usage is above the given limit.
     * If no limit is given, it will use 75% of the maximum memory as limit.
     *
     * @param int|null $memoryLimit The memory limit in bytes. If null, it will use 75% of the maximum memory.
     * @return bool true if the resources were released, false if nothing was done.
     */
    public function releaseIfOutOfMemory(?int $memoryLimit = null): bool
    {
        $shouldRelease = $memoryLimit === null
            ? Memory::usage() >= 0.75
            : Memory::current() >= $memoryLimit
        ;

        if ($shouldRelease) {
            $this->release();
        }

        return $shouldRelease;
    }

    /**
     * @return array<int, LosslessImageDefinition>
     * @throws ParserExceptionInterface
     */
    private function extractLosslessImages(): array
    {
        $images = [];

        foreach ($this->file->tags(DefineBitsLosslessTag::TYPE_V1, DefineBitsLosslessTag::TYPE_V2) as $pos => $tag) {
            assert($tag instanceof DefineBitsLosslessTag);

            if (($id = $pos->id) === null) {
                continue;
            }

            $images[$id] = new LosslessImageDefinition($tag);
        }

        return $images;
    }

    /**
     * @return array<int, ImageBitsDefinition>
     * @throws ParserExceptionInterface
     */
    private function extractDefineBits(): array
    {
        $images = [];
        $jpegTables = null;

        /** @var JPEGTablesTag|DefineBitsTag $tag */
        foreach ($this->file->tags(JPEGTablesTag::TYPE, DefineBitsTag::TYPE) as $tag) {
            if ($tag instanceof JPEGTablesTag) {
                $jpegTables = $tag;
                continue;
            }

            if ($jpegTables === null) {
                continue; // JPEGTablesTag must be before DefineBitsTag
            }

            assert($tag instanceof DefineBitsTag);
            $images[$tag->characterId] = new ImageBitsDefinition($tag, $jpegTables);
        }

        return $images;
    }

    /**
     * @return array<int, JpegImageDefinition>
     * @throws ParserExceptionInterface
     */
    private function extractJpeg(): array
    {
        $images = [];

        foreach ($this->file->tags(DefineBitsJPEG2Tag::TYPE, DefineBitsJPEG3Tag::TYPE, DefineBitsJPEG4Tag::TYPE) as $pos => $tag) {
            assert($tag instanceof DefineBitsJPEG2Tag || $tag instanceof DefineBitsJPEG3Tag || $tag instanceof DefineBitsJPEG4Tag);

            if (($id = $pos->id) === null) {
                continue;
            }

            $images[$id] = new JpegImageDefinition($tag);
        }

        return $images;
    }
}
