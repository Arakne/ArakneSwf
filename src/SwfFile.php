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
use Arakne\Swf\Error\Errors;
use Arakne\Swf\Extractor\Image\ImageBitsDefinition;
use Arakne\Swf\Extractor\Image\JpegImageDefinition;
use Arakne\Swf\Extractor\Image\LosslessImageDefinition;
use Arakne\Swf\Extractor\MissingCharacter;
use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Extractor\Timeline\Timeline;
use Arakne\Swf\Parser\Error\ParserExceptionInterface;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\SwfHeader;
use Arakne\Swf\Parser\Structure\SwfTag;
use Arakne\Swf\Parser\Structure\Tag\DoActionTag;
use Arakne\Swf\Parser\Swf;
use Arakne\Swf\Parser\SwfReader;
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
    public const int MAX_FRAME_RATE = 120;

    private ?Swf $parser = null;

    public function __construct(
        /**
         * The path to the SWF file.
         */
        public readonly string $path,

        /**
         * Configure the error reporting.
         *
         * Enabling all errors will stop the parsing on the first, malformed or unexpected data.
         * This can improve security, but may also fail to parse some legitimate SWF files.
         *
         * Disabling all errors will lead to fail-safe parsing, so it will try to parse as much as possible,
         * even missing or malformed data, without throwing any exception.
         * This allows to extract from corrupted files, but may lead to unexpected results and security issues.
         */
        public readonly int $errors = Errors::ALL,
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

        $reader = new SwfReader($head);
        $signature = $reader->readBytes(3);

        if ($signature !== 'CWS' && $signature !== 'FWS') {
            return false;
        }

        $version = $reader->readUI8();

        // Last version (2024) is 51, so we can safely assume that any version above 60 is invalid
        if ($version > 60) {
            return false;
        }

        $len = $reader->readUI32();

        if ($len > $maxLength) {
            return false;
        }

        return true;
    }

    /**
     * Get the SWF file header.
     *
     * @throws ParserExceptionInterface
     */
    public function header(): SwfHeader
    {
        return $this->parser()->header;
    }

    /**
     * Get the display size of SWF file frames.
     *
     * @throws ParserExceptionInterface
     */
    public function displayBounds(): Rectangle
    {
        return $this->parser()->header->frameSize;
    }

    /**
     * Get the frame rate of the SWF file.
     *
     * @return positive-int
     * @throws ParserExceptionInterface
     */
    public function frameRate(): int
    {
        $rate = (int) $this->parser()->header->frameRate;

        // When the frame rate is 0, it should be considered as the maximum frame rate.
        // See: https://www.m2osw.com/swf_tag_file_header#comment-1345
        // Negative values are possible due to the usage of fixed8, but older specs define it as UI16,
        // so we consider them same as 0.
        if ($rate <= 0) {
            return self::MAX_FRAME_RATE;
        }

        return $rate > self::MAX_FRAME_RATE ? self::MAX_FRAME_RATE : $rate;
    }

    /**
     * Extract and parse tags from the SWF file.
     *
     * The key of the result is the tag position and id, and the value is the parsed tag.
     *
     * @param int ...$tagIds The tag IDs to extract. If empty, all tags are extracted.
     *
     * @return iterable<SwfTag, object>
     * @throws ParserExceptionInterface
     */
    public function tags(int ...$tagIds): iterable
    {
        $parser = $this->parser();
        $ignoreInvalidTags = ($this->errors & Errors::INVALID_TAG) === 0;

        if ($tagIds) {
            $tagIds = array_flip($tagIds);
        }

        foreach ($parser->tags as $tag) {
            if (!$tagIds || isset($tagIds[$tag->type])) {
                try {
                    yield $tag => $parser->parse($tag);
                } catch (ParserExceptionInterface $e) {
                    if (!$ignoreInvalidTags) {
                        throw $e;
                    }
                }
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
     * @throws ParserExceptionInterface
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
     *
     * @return array<string, mixed>
     * @throws ParserExceptionInterface
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
     * @throws ParserExceptionInterface
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
     *
     * @return ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition
     * @throws ParserExceptionInterface
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
     * Note: Due to the way PHP handles array keys, numeric keys will be converted to integers.
     *       So don't forget to cast them to string if you need to use them as strings.
     *
     * @return array<array-key, ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition>
     * @throws ParserExceptionInterface
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
     * @throws ParserExceptionInterface
     *
     * @see SwfExtractor::timeline()
     */
    public function timeline(bool $useFileDisplayBounds = true): Timeline
    {
        $extractor = new SwfExtractor($this);

        return $extractor->timeline($useFileDisplayBounds);
    }

    /**
     * @return Swf
     * @throws ParserExceptionInterface
     */
    private function parser(): Swf
    {
        return $this->parser ??= Swf::fromString(
            file_get_contents($this->path) ?: throw new InvalidArgumentException('Unable to read the file'),
            $this->errors,
        );
    }
}
