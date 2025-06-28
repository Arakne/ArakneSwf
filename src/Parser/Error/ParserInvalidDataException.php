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

use Throwable;
use UnexpectedValueException;

use function error_get_last;
use function sprintf;

/**
 * Exception thrown when the input data is invalid or corrupted.
 */
final class ParserInvalidDataException extends UnexpectedValueException implements ParserExceptionInterface
{
    /**
     * @var non-negative-int
     */
    public readonly int $offset;

    /**
     * @param string $message
     * @param non-negative-int $offset
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $offset, ?Throwable $previous = null)
    {
        parent::__construct($message, Errors::INVALID_DATA, $previous);

        $this->offset = $offset;
    }

    /**
     * @param non-negative-int $offset
     * @return self
     */
    public static function createInvalidCompressedData(int $offset): self
    {
        $lastError = error_get_last()['message'] ?? 'Unknown error';

        return new self(
            sprintf('Invalid compressed data at offset %d: %s', $offset, $lastError),
            $offset
        );
    }
}
