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

namespace Arakne\Swf\Extractor\Error;

use Arakne\Swf\Error\Errors;
use RuntimeException;

/**
 * Exception thrown when a circular reference is detected in the SWF character definitions.
 * This typically occurs when a character references itself directly or indirectly, creating an infinite loop.
 *
 * @see Errors::CIRCULAR_REFERENCE
 */
final class CircularReferenceException extends RuntimeException implements ExtractorExceptionInterface
{
    public function __construct(
        string $message,
        public readonly int $characterId,
    ) {
        parent::__construct($message, Errors::CIRCULAR_REFERENCE);
    }
}
