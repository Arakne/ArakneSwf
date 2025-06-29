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

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Error\Errors;
use Arakne\Swf\Parser\Error\ParserExceptionInterface;
use Arakne\Swf\Parser\Structure\SwfTag;
use Arakne\Swf\Parser\SwfReader;

final readonly class DefineSpriteTag
{
    public const int TYPE = 39;

    public function __construct(
        public int $spriteId,
        public int $frameCount,
        /**
         * @var list<object>
         */
        public array $tags,
    ) {}

    /**
     * Read a DefineSprite tag
     *
     * @param SwfReader $reader
     * @param non-negative-int $swfVersion The version of the SWF file
     * @param non-negative-int $end The end byte offset of the tag.
     *
     * @return self
     * @throws ParserExceptionInterface
     */
    public static function read(SwfReader $reader, int $swfVersion, int $end): self
    {
        $ignoreTagError = ($reader->errors & Errors::INVALID_TAG) === 0;
        $spriteId = $reader->readUI16();
        $frameCount = $reader->readUI16();

        // Collect and parse tags
        $tags = [];

        foreach (SwfTag::readAll($reader, $end, false) as $tag) {
            try {
                $tags[] = $tag->parse($reader, $swfVersion);
            } catch (ParserExceptionInterface $e) {
                if (!$ignoreTagError) {
                    throw $e;
                }
            }
        }

        return new DefineSpriteTag(
            spriteId: $spriteId,
            frameCount: $frameCount,
            tags: $tags,
        );
    }
}
