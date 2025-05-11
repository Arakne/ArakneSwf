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

namespace Arakne\Swf;

use Arakne\Swf\Avm\Processor;
use Arakne\Swf\Avm\State;
use Arakne\Swf\Extractor\Image\ImageBitsDefinition;
use Arakne\Swf\Extractor\Image\JpegImageDefinition;
use Arakne\Swf\Extractor\Image\LosslessImageDefinition;
use Arakne\Swf\Extractor\MissingCharacter;
use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Extractor\Timeline\Timeline;
use Arakne\Swf\Parser\Error\ErrorCollector;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\SwfTagPosition;
use Arakne\Swf\Parser\Structure\Tag\DoActionTag;
use Arakne\Swf\Parser\Swf;
use Arakne\Swf\Parser\SwfIO;
use InvalidArgumentException;

use function array_flip;
use function assert;
use function file_get_contents;
use function strlen;

/**
 * Facade for extracting information from a SWF file.
 */
final class SwfFile
{
    private ?Swf $parser = null;

    public function __construct(
        /**
         * The path to the SWF file.
         */
        public readonly string $path,

        /**
         * Allow to collect errors during parsing.
         * If not set, errors will be ignored.
         */
        public readonly ?ErrorCollector $errorCollector = null,
    ) {}

    /**
     * Check if the given file is a valid SWF file.
     *
     * This method will only check for the SWF header, not the whole file.
     * So the content may be corrupted or incomplete.
     *
     * @param int $maxLength The maximum length of the decompressed file in bytes.
     *
     * @return bool True if the file is a valid SWF file.
     */
    public function valid(int $maxLength = 512_000_000): bool
    {
        // Read only the first header part
        $head = file_get_contents($this->path, false, null, 0, 8);

        if ($head === false || strlen($head) < 8) {
            return false;
        }

        $io = new SwfIO($head);
        $signature = $io->collectBytes(3);

        if ($signature !== 'CWS' && $signature !== 'FWS') {
            return false;
        }

        $version = $io->collectUI8();

        // Last version (2024) is 51, so we can safely assume that any version above 60 is invalid
        if ($version > 60) {
            return false;
        }

        $len = $io->collectUI32();

        if ($len > $maxLength) {
            return false;
        }

        return true;
    }

    /**
     * Get the display size of SWF file frames.
     */
    public function displayBounds(): Rectangle
    {
        return $this->parser()->header->frameSize;
    }

    /**
     * Extract and parse tags from the SWF file.
     *
     * The key of the result is the tag position and id, and the value is the parsed tag.
     *
     * @param int ...$tagIds The tag IDs to extract. If empty, all tags are extracted.
     *
     * @return iterable<SwfTagPosition, object>
     */
    public function tags(int ...$tagIds): iterable
    {
        $parser = $this->parser();

        if ($tagIds) {
            $tagIds = array_flip($tagIds);
        }

        foreach ($parser->tags as $tag) {
            if (!$tagIds || isset($tagIds[$tag->type])) {
                yield $tag => $parser->parseTag($tag);
            }
        }
    }

    /**
     * Execute DoAction tags and return the final state.
     * The method may be dangerous if the SWF file contains malicious code, so call it only if you trust the source.
     *
     * Note: By default, the function call is disabled. You can enable it by passing a custom Processor with allowFunctionCall set to true.
     *
     * @param State|null $state The initial state. If null, a new state is created.
     * @param Processor|null $processor The execution processor. If null, a new processor is created with default settings.
     *
     * @return State
     */
    public function execute(?State $state = null, ?Processor $processor = null): State
    {
        $processor ??= new Processor(allowFunctionCall: false);
        $state ??= new State();

        // @todo handle InitActionTag
        foreach ($this->tags(DoActionTag::TYPE) as $tag) {
            assert($tag instanceof DoActionTag);

            $state = $processor->run($tag->actions, $state);
        }

        return $state;
    }

    /**
     * Execute DoAction tags and return all global variables.
     * The method may be dangerous if the SWF file contains malicious code, so call it only if you trust the source.
     *
     * Note: By default, the function call is disabled. You can enable it by passing a custom Processor with allowFunctionCall set to true.
     *
     * @param State|null $state The initial state. If null, a new state is created.
     * @param Processor|null $processor The execution processor. If null, a new processor is created with default settings.
     * @return array<string, mixed>
     */
    public function variables(?State $state = null, ?Processor $processor = null): array
    {
        return $this->execute($state, $processor)->variables;
    }

    /**
     * Extract an asset by its exported name.
     *
     * This method is a helper method which simply calls `(new SwfExtractor($this))->byName($name)`.
     * If you want to extract multiple assets, you should use the {@see SwfExtractor} class directly,
     * which can rely on memory caching.
     *
     * @param string $name The name of the asset to extract.
     * @return ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition
     *
     * @throws InvalidArgumentException When the name is not exported.
     *
     * @see SwfExtractor::byName()
     */
    public function assetByName(string $name): ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition
    {
        $extractor = new SwfExtractor($this);

        return $extractor->byName($name);
    }

    /**
     * Extract an asset by its character ID.
     * If the character ID is not found, a {@see MissingCharacter} will be returned.
     *
     * This method is a helper method which simply calls `(new SwfExtractor($this))->character($id)`.
     * If you want to extract multiple assets, you should use the {@see SwfExtractor} class directly,
     * which can rely on memory caching.
     *
     * @param int $id The character ID of the asset to extract.
     * @return ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition
     *
     * @see SwfExtractor::character()
     */
    public function assetById(int $id): ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition
    {
        $extractor = new SwfExtractor($this);

        return $extractor->character($id);
    }

    /**
     * Get all exported assets, and return them as an associative array indexed by their name.
     *
     * @return array<string, ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition>
     *
     * @see SwfExtractor::exported()
     * @see SwfExtractor::character()
     */
    public function exportedAssets(): array
    {
        $assets = [];
        $extractor = new SwfExtractor($this);

        foreach ($extractor->exported() as $name => $id) {
            $assets[$name] = $extractor->character($id);
        }

        return $assets;
    }

    /**
     * Get the root swf file timeline animation.
     *
     * This method is a helper method which simply calls `(new SwfExtractor($this))->timeline()`.
     * If you want to extract multiple assets, you should use the {@see SwfExtractor} class directly,
     * which can rely on memory caching.
     *
     * @param bool $useFileDisplayBounds If true, the timeline will be adjusted to the file display bounds (i.e. {@see SwfFile::displayBounds()}). If false, the bounds will be the highest bounds of all frames.
     *
     * @return Timeline
     *
     * @see SwfExtractor::timeline()
     */
    public function timeline(bool $useFileDisplayBounds = true): Timeline
    {
        $extractor = new SwfExtractor($this);

        return $extractor->timeline($useFileDisplayBounds);
    }

    private function parser(): Swf
    {
        return $this->parser ??= new Swf(
            file_get_contents($this->path) ?: throw new InvalidArgumentException('Unable to read the file'),
            $this->errorCollector
        );
    }
}
