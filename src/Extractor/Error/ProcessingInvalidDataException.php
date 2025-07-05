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

namespace Arakne\Swf\Extractor\Error;

use Arakne\Swf\Error\Errors;
use Throwable;
use UnexpectedValueException;

/**
 * Exception thrown when the extractor encounters invalid data during processing.
 * This typically indicates that the data does not conform to the expected format or structure.
 */
final class ProcessingInvalidDataException extends UnexpectedValueException implements ExtractorExceptionInterface
{
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, Errors::UNPROCESSABLE_DATA, $previous);
    }
}
