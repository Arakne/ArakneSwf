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

namespace Arakne\Swf\Extractor;

/**
 * Extends DrawableInterface to support ratio-based drawing (e.g. morph shapes)
 */
interface RatioDrawableInterface extends DrawableInterface
{
    public const int MAX_RATIO = 65535;

    /**
     * Return a drawable instance at the given ratio
     * The returned type may be the same as the current instance.
     *
     * @param int<0, 65535> $ratio
     * @return DrawableInterface
     */
    public function withRatio(int $ratio): DrawableInterface;
}
