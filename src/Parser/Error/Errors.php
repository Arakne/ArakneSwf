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

namespace Arakne\Swf\Parser\Error;

/**
 * Constants for error flags.
 */
final readonly class Errors
{
    /**
     * Disable all error flags.
     */
    public const int NONE = 0;

    /**
     * Enable all error flags.
     */
    public const int ALL = -1;

    /**
     * Trying to access data after the end of the input stream.
     */
    public const int OUT_OF_BOUNDS = 1;

    /**
     * The input data is invalid or corrupted.
     */
    public const int INVALID_DATA = 2;

    /**
     * The input data has more data than expected (i.e. not all data was consumed).
     */
    public const int EXTRA_DATA = 4;
}
