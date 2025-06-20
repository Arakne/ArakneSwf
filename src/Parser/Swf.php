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
 * Copyright (C) 2025 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Parser;

use Arakne\Swf\Parser\Error\ErrorCollector;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\SwfHeader;
use Arakne\Swf\Parser\Structure\SwfTag;

use InvalidArgumentException;

use function assert;
use function iterator_to_array;
use function sprintf;

/**
 * Facade for parse and extract data from a SWF file
 */
final readonly class Swf
{
    public function __construct(
        private SwfReader $reader,
        public SwfHeader $header,

        /**
         * All tags contained in the SWF file
         *
         * @var list<SwfTag>
         */
        public array $tags,
        private ?ErrorCollector $errorCollector = null, // @todo to remove
    ) {
        // @todo tag dictionary
    }

    /**
     * Parse the given tag data
     *
     * @param SwfTag $tag
     * @return object
     *
     * @see SwfTag::parse()
     */
    final public function parse(SwfTag $tag): object
    {
        return $tag->parse($this->reader, $this->header->version, $this->errorCollector);
    }

    /**
     * Parse the SWF data from a string
     *
     * @param string $data The binary SWF data
     * @param ErrorCollector|null $errorCollector
     *
     * @return self
     */
    public static function fromString(string $data, ?ErrorCollector $errorCollector = null): self
    {
        $reader = new SwfReader($data);

        return self::read($reader, $errorCollector);
    }

    /**
     * Create the Swf object from a SWF file reader
     *
     * @param SwfReader $reader
     * @param ErrorCollector|null $errorCollector
     *
     * @return self
     */
    public static function read(SwfReader $reader, ?ErrorCollector $errorCollector = null): self
    {
        $signature = $reader->readBytes(3);

        if ($signature === 'CWS') {
            $compressed = true;
        } elseif ($signature === 'FWS') {
            $compressed = false;
        } else {
            throw new InvalidArgumentException(sprintf('Unsupported SWF signature: %s', $signature));
        }

        $version = $reader->readUI8();
        $fileLength = $reader->readUI32();

        if ($fileLength < 8) {
            throw new InvalidArgumentException(sprintf('Invalid SWF file length: %d', $fileLength));
        }

        if ($compressed) {
            $reader = $reader->uncompress($fileLength);
        }

        $frameSize = Rectangle::read($reader);
        $frameRate = $reader->readFixed8();
        $frameCount = $reader->readUI16();

        $header = new SwfHeader(
            $signature,
            $version,
            $fileLength,
            $frameSize,
            $frameRate,
            $frameCount,
        );

        $tags = iterator_to_array(SwfTag::readAll($reader), false);

        return new self($reader, $header, $tags, $errorCollector);
    }
}
