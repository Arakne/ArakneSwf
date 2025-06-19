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

namespace Arakne\Swf\Parser\Structure;

use Arakne\Swf\Parser\Structure\Tag\DefineBinaryDataTag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG3Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsLosslessTag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsTag;
use Arakne\Swf\Parser\Structure\Tag\DefineButton2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineButtonTag;
use Arakne\Swf\Parser\Structure\Tag\DefineEditTextTag;
use Arakne\Swf\Parser\Structure\Tag\DefineFont2Or3Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineFont4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineFontTag;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShape2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShapeTag;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\Parser\Structure\Tag\DefineSoundTag;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Arakne\Swf\Parser\Structure\Tag\DefineTextTag;
use Arakne\Swf\Parser\Structure\Tag\DefineVideoStreamTag;
use Arakne\Swf\Parser\SwfReader;

use function strlen;

/**
 * Structure for get tag offset and length before parsing
 *
 * @todo rename to SwfTag
 */
final readonly class SwfTagPosition
{
    /**
     * Map of tag types that are definition tags (i.e. has a character id).
     * The key and the value are the same.
     *
     * @var array<int, int>
     */
    public const array DEFINITION_TAG_TYPES = [
        DefineShapeTag::TYPE_V1 => DefineShapeTag::TYPE_V1,
        DefineShapeTag::TYPE_V2 => DefineShapeTag::TYPE_V2,
        DefineShapeTag::TYPE_V3 => DefineShapeTag::TYPE_V3,
        DefineShape4Tag::TYPE_V4 => DefineShape4Tag::TYPE_V4,
        DefineFontTag::TYPE_V1 => DefineFontTag::TYPE_V1,
        DefineFont2Or3Tag::TYPE_V2 => DefineFont2Or3Tag::TYPE_V2,
        DefineFont2Or3Tag::TYPE_V3 => DefineFont2Or3Tag::TYPE_V3,
        DefineFont4Tag::TYPE_V4 => DefineFont4Tag::TYPE_V4,
        DefineButtonTag::TYPE => DefineButtonTag::TYPE,
        DefineButton2Tag::TYPE => DefineButton2Tag::TYPE,
        DefineSoundTag::TYPE => DefineSoundTag::TYPE,
        DefineSpriteTag::TYPE => DefineSpriteTag::TYPE,
        DefineTextTag::TYPE_V1 => DefineTextTag::TYPE_V1,
        DefineTextTag::TYPE_V2 => DefineTextTag::TYPE_V2,
        DefineBitsLosslessTag::TYPE_V1 => DefineBitsLosslessTag::TYPE_V1,
        DefineBitsLosslessTag::TYPE_V2 => DefineBitsLosslessTag::TYPE_V2,
        DefineBitsTag::TYPE => DefineBitsTag::TYPE,
        DefineBitsJPEG2Tag::TYPE => DefineBitsJPEG2Tag::TYPE,
        DefineBitsJPEG3Tag::TYPE => DefineBitsJPEG3Tag::TYPE,
        DefineBitsJPEG4Tag::TYPE => DefineBitsJPEG4Tag::TYPE,
        DefineEditTextTag::TYPE => DefineEditTextTag::TYPE,
        DefineMorphShapeTag::TYPE => DefineMorphShapeTag::TYPE,
        DefineMorphShape2Tag::TYPE => DefineMorphShape2Tag::TYPE,
        DefineVideoStreamTag::TYPE => DefineVideoStreamTag::TYPE,
        DefineBinaryDataTag::TYPE => DefineBinaryDataTag::TYPE,
    ];

    public function __construct(
        public int $type,
        /** @var non-negative-int */
        public int $offset,
        /** @var non-negative-int */
        public int $length,

        /**
         * The tag id is set only in case of a definition tag (e.g. DefineXXX)
         */
        public ?int $id = null,
    ) {}

    /**
     * Read all tags from the SWF file.
     * Stops reading when the end of the file is reached.
     *
     * @return list<self>
     */
    public static function readAll(SwfReader $reader): array
    {
        $tags = [];
        $len = strlen($reader->b); // @todo length property on SwfReader

        while ($reader->offset < $len) {
            $recordHeader = $reader->readUI16();
            $tagType = $recordHeader >> 6;
            $tagLength = $recordHeader & 0x3f;

            if ($tagLength === 0x3f) {
                $tagLength = $reader->readUI32();
            }

            if (self::isDefinitionTagType($tagType) && $tagLength >= 2) {
                // The two following bytes are the character id for definition tags
                $tags[] = new SwfTagPosition(
                    type: $tagType,
                    offset: $reader->offset,
                    length: $tagLength,
                    id: $reader->readUI16(),
                );
                $reader->skipBytes($tagLength - 2); // 2 bytes already consumed
            } else {
                $tags[] = new SwfTagPosition(
                    type: $tagType,
                    offset: $reader->offset,
                    length: $tagLength,
                );
                $reader->skipBytes($tagLength);
            }
        }

        return $tags;
    }

    /**
     * Check if the given tag type is a definition tag type (i.e. has a character id).
     *
     * @param int $type The tag type to check
     * @return bool
     */
    private static function isDefinitionTagType(int $type): bool
    {
        return isset(self::DEFINITION_TAG_TYPES[$type]);
    }
}
