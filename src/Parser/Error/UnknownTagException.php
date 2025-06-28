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

use UnexpectedValueException;

use function sprintf;

/**
 * Exception thrown when an unknown tag is encountered in the SWF file.
 */
final class UnknownTagException extends UnexpectedValueException implements ParserExceptionInterface
{
    public function __construct(
        /**
         * @var non-negative-int
         */
        public readonly int $tagCode,

        /**
         * @var non-negative-int
         */
        public readonly int $offset,
    ) {
        parent::__construct(
            sprintf('Unknown tag with code %d at offset %d', $tagCode, $offset),
            Errors::UNKNOWN_TAG
        );
    }
}
