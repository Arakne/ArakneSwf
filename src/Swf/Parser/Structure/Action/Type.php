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

namespace Arakne\Swf\Parser\Structure\Action;

/**
 * Enum of types used in action script
 */
enum Type: int
{
    /** Null-terminated string */
    case String = 0;
    /** 32 bits float */
    case Float = 1;
    case Null = 2;
    case Undefined = 3;
    /** Register number. Unsigned 8 bits */
    case Register = 4;
    case Boolean = 5;
    /** 64 bits float */
    case Double = 6;
    /** 32 bits signed integer */
    case Integer = 7;
    /** 8 bits constant id. Reference to constant pool. */
    case Constant8 = 8;
    /** 16 bits constant id. Reference to constant pool. */
    case Constant16 = 9;
}
