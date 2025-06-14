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

namespace Arakne\Swf\Parser\Structure\Record\Filter;

use Arakne\Swf\Parser\SwfReader;
use Exception;

use function sprintf;

/**
 * Base type for graphic filters.
 */
abstract readonly class Filter
{
    /**
     * Reads a collection of filters from the SWF reader.
     * The collection size is provided by the first byte.
     *
     * @return list<DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter>
     */
    final public static function readCollection(SwfReader $reader): array
    {
        $filters = [];
        $count = $reader->readUI8();

        for ($f = 0; $f < $count; $f++) {
            $filterId = $reader->readUI8();

            $filters[] = match ($filterId) {
                DropShadowFilter::FILTER_ID => DropShadowFilter::read($reader),
                BlurFilter::FILTER_ID => BlurFilter::read($reader),
                GlowFilter::FILTER_ID => GlowFilter::read($reader),
                BevelFilter::FILTER_ID => BevelFilter::read($reader),
                GradientGlowFilter::FILTER_ID => GradientGlowFilter::read($reader),
                ConvolutionFilter::FILTER_ID => ConvolutionFilter::read($reader),
                ColorMatrixFilter::FILTER_ID => ColorMatrixFilter::read($reader),
                GradientBevelFilter::FILTER_ID => GradientBevelFilter::read($reader),
                default => throw new Exception(sprintf('Unsupported filter id %d', $filterId)),
            };
        }

        return $filters;
    }

    /**
     * Reads a single filter from the SWF reader.
     *
     * The filter type has already been determined by the caller,
     * so this method only needs to read the filter data
     *
     * @param SwfReader $reader The SWF reader to read from.
     * @return static The filter instance.
     */
    abstract protected static function read(SwfReader $reader): static;
}
