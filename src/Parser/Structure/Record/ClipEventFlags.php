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

namespace Arakne\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\SwfReader;

/**
 * Bitfield wrapper for clip event flags.
 * Flags can be 16 or 32 bits long, depending on the SWF version (16 for <= 5, 32 for >= 6).
 */
final readonly class ClipEventFlags
{
    // First byte
    public const int KEY_UP = 0x80;
    public const int KEY_DOWN = 0x40;
    public const int MOUSE_UP = 0x20;
    public const int MOUSE_DOWN = 0x10;
    public const int MOUSE_MOVE = 0x08;
    public const int UNLOAD = 0x04;
    public const int ENTER_FRAME = 0x02;
    public const int LOAD = 0x01;

    // Second byte
    public const int DRAG_OVER = 0x8000;
    public const int ROLL_OUT = 0x4000;
    public const int ROLL_OVER = 0x2000;
    public const int RELEASE_OUTSIDE = 0x1000;
    public const int RELEASE = 0x0800;
    public const int PRESS = 0x0400;
    public const int INITIALIZE = 0x0200;
    public const int DATA = 0x0100;

    // Third byte (SWF >= 6)
    public const int CONSTRUCT = 0x040000;
    public const int KEY_PRESS = 0x020000;
    public const int DRAG_OUT = 0x010000;

    public function __construct(public int $flags) {}

    /**
     * Check if the given flag is set in the bitfield.
     *
     * @param int $flag The flag to check. Should be one of the ClipEventFlags constants.
     *
     * @return bool True if the flag is set, false otherwise
     */
    public function has(int $flag): bool
    {
        return ($this->flags & $flag) === $flag;
    }

    /**
     * Create a ClipEventFlags instance by reading from the SWF reader.
     *
     * @param SwfReader $reader
     * @param int $version The SWF version. If <= 5, the flags are 16 bits long, otherwise they are 32 bits long.
     *
     * @return self
     */
    public static function read(SwfReader $reader, int $version): self
    {
        return new self($version <= 5 ? $reader->readUI16() : $reader->readUI32());
    }
}
