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

namespace Arakne\Swf\Parser\Error;

use Arakne\Swf\Parser\Structure\SwfTagPosition;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * This class collect and handle errors during the parsing of a SWF file.
 *
 * @implements IteratorAggregate<int, TagParseError>
 */
final class ErrorCollector implements IteratorAggregate
{
    /**
     * @var list<TagParseError>
     */
    private array $errors = [];

    public function __construct(
        /**
         * If set to true, an exception will be thrown when an error occurs.
         *
         * @see TagParseException
         */
        private readonly bool $throwOnError = false,
    ) {}

    #[Override]
    public function getIterator(): Traversable
    {
        yield from $this->errors;
    }

    /**
     * Add an error to the collector.
     *
     * @param SwfTagPosition $position
     * @param TagParseErrorType $error
     * @param array $payload
     */
    public function add(SwfTagPosition $position, TagParseErrorType $error, array $payload = []): void
    {
        $this->errors[] = $error = new TagParseError($position, $error, $payload);

        if ($this->throwOnError) {
            throw new TagParseException($error);
        }
    }
}
