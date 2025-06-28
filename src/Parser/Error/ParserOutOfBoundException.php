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

namespace Arakne\Swf\Parser\Error;

use OutOfBoundsException;

use function sprintf;

/**
 * Exception thrown when a parser tries to access data after the end of the input stream.
 */
final class ParserOutOfBoundException extends OutOfBoundsException implements ParserExceptionInterface
{
    /**
     * @var non-negative-int
     */
    public readonly int $offset;

    /**
     * @param string $message
     * @param non-negative-int $offset
     */
    public function __construct(string $message, int $offset)
    {
        parent::__construct($message, Errors::OUT_OF_BOUNDS);

        $this->offset = $offset;
    }

    /**
     * @param non-negative-int $offset
     * @param non-negative-int $end
     *
     * @return self
     */
    public static function createReadAfterEnd(int $offset, int $end): self
    {
        return new self(
            sprintf('Trying to access data after the end of the input stream (offset: %d, end: %d)', $offset, $end),
            $offset
        );
    }

    /**
     * @param non-negative-int $offset
     * @param non-negative-int $end
     * @param non-negative-int $bytes
     *
     * @return self
     */
    public static function createReadTooManyBytes(int $offset, int $end, int $bytes): self
    {
        return new self(
            sprintf('Cannot read %s bytes from offset %s, end is at %s', $bytes, $offset, $end),
            $offset
        );
    }
}
