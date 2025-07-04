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

use Arakne\Swf\Error\Errors;
use Arakne\Swf\Parser\Error\ParserExceptionInterface;
use Arakne\Swf\Parser\Error\ParserExtraDataException;
use Arakne\Swf\Parser\Structure\Tag\CSMTextSettingsTag;
use Arakne\Swf\Parser\Structure\Tag\DefineBinaryDataTag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG3Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsLosslessTag;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsTag;
use Arakne\Swf\Parser\Structure\Tag\DefineButton2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineButtonCxformTag;
use Arakne\Swf\Parser\Structure\Tag\DefineButtonSoundTag;
use Arakne\Swf\Parser\Structure\Tag\DefineButtonTag;
use Arakne\Swf\Parser\Structure\Tag\DefineEditTextTag;
use Arakne\Swf\Parser\Structure\Tag\DefineFont2Or3Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineFont4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineFontAlignZonesTag;
use Arakne\Swf\Parser\Structure\Tag\DefineFontInfoTag;
use Arakne\Swf\Parser\Structure\Tag\DefineFontNameTag;
use Arakne\Swf\Parser\Structure\Tag\DefineFontTag;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShape2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShapeTag;
use Arakne\Swf\Parser\Structure\Tag\DefineScalingGridTag;
use Arakne\Swf\Parser\Structure\Tag\DefineSceneAndFrameLabelDataTag;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\Parser\Structure\Tag\DefineSoundTag;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Arakne\Swf\Parser\Structure\Tag\DefineTextTag;
use Arakne\Swf\Parser\Structure\Tag\DefineVideoStreamTag;
use Arakne\Swf\Parser\Structure\Tag\DoABCTag;
use Arakne\Swf\Parser\Structure\Tag\DoActionTag;
use Arakne\Swf\Parser\Structure\Tag\DoInitActionTag;
use Arakne\Swf\Parser\Structure\Tag\EnableDebuggerTag;
use Arakne\Swf\Parser\Structure\Tag\EndTag;
use Arakne\Swf\Parser\Structure\Tag\ExportAssetsTag;
use Arakne\Swf\Parser\Structure\Tag\FileAttributesTag;
use Arakne\Swf\Parser\Structure\Tag\FrameLabelTag;
use Arakne\Swf\Parser\Structure\Tag\ImportAssetsTag;
use Arakne\Swf\Parser\Structure\Tag\JPEGTablesTag;
use Arakne\Swf\Parser\Structure\Tag\MetadataTag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObject2Tag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObject3Tag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObjectTag;
use Arakne\Swf\Parser\Structure\Tag\ProductInfo;
use Arakne\Swf\Parser\Structure\Tag\ProtectTag;
use Arakne\Swf\Parser\Structure\Tag\ReflexTag;
use Arakne\Swf\Parser\Structure\Tag\RemoveObject2Tag;
use Arakne\Swf\Parser\Structure\Tag\RemoveObjectTag;
use Arakne\Swf\Parser\Structure\Tag\ScriptLimitsTag;
use Arakne\Swf\Parser\Structure\Tag\SetBackgroundColorTag;
use Arakne\Swf\Parser\Structure\Tag\SetTabIndexTag;
use Arakne\Swf\Parser\Structure\Tag\ShowFrameTag;
use Arakne\Swf\Parser\Structure\Tag\SoundStreamBlockTag;
use Arakne\Swf\Parser\Structure\Tag\SoundStreamHeadTag;
use Arakne\Swf\Parser\Structure\Tag\StartSound2Tag;
use Arakne\Swf\Parser\Structure\Tag\StartSoundTag;
use Arakne\Swf\Parser\Structure\Tag\SymbolClassTag;
use Arakne\Swf\Parser\Structure\Tag\UnknownTag;
use Arakne\Swf\Parser\Structure\Tag\VideoFrameTag;
use Arakne\Swf\Parser\SwfReader;

use function assert;
use function sprintf;

/**
 * Structure for the tag before parsing.
 * To get the actual structure, use the `parse()` method.
 */
final readonly class SwfTag
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
        /**
         * The tag type, as defined in the SWF specification.
         *
         * @var non-negative-int
         */
        public int $type,

        /**
         * Byte offset of the start of the tag data, after the tag header (type and length).
         * In case of empty tag (i.e. length is 0), this offset is after the end of the tag.
         *
         * @var non-negative-int
         */
        public int $offset,

        /**
         * The length of the tag data, in bytes.
         * The length ignore the tag header (type and length).
         *
         * @var non-negative-int
         */
        public int $length,

        /**
         * The tag id is set only in case of a definition tag (e.g. DefineXXX)
         *
         * @var non-negative-int|null
         */
        public ?int $id = null,
    ) {}

    /**
     * Parse the tag structure
     *
     * @param SwfReader $reader The base reader to use for parsing. This reader will not be modified, and a new reader will be created for the tag data.
     * @param non-negative-int $swfVersion The SWF version of the file being parsed
     *
     * @return object
     * @throws ParserExceptionInterface
     */
    public function parse(SwfReader $reader, int $swfVersion): object
    {
        $bytePosEnd = $this->offset + $this->length;
        $reader = $reader->chunk($this->offset, $bytePosEnd);

        if ($bytePosEnd > $reader->end) {
            $bytePosEnd = $reader->end;
        }

        $ret = match ($this->type) {
            EndTag::TYPE => new EndTag(),
            ShowFrameTag::TYPE => new ShowFrameTag(),
            DefineShapeTag::TYPE_V1 => DefineShapeTag::read($reader, 1),
            PlaceObjectTag::TYPE => PlaceObjectTag::read($reader, $bytePosEnd),
            RemoveObjectTag::TYPE => RemoveObjectTag::read($reader),
            DefineBitsTag::TYPE => DefineBitsTag::read($reader, $bytePosEnd),
            DefineButtonTag::TYPE => DefineButtonTag::read($reader, $bytePosEnd),
            JPEGTablesTag::TYPE => JPEGTablesTag::read($reader, $bytePosEnd),
            SetBackgroundColorTag::TYPE => SetBackgroundColorTag::read($reader),
            DefineFontTag::TYPE_V1 => DefineFontTag::read($reader),
            DefineTextTag::TYPE_V1 => DefineTextTag::read($reader, 1),
            DoActionTag::TYPE => DoActionTag::read($reader, $bytePosEnd),
            DefineFontInfoTag::TYPE_V1 => DefineFontInfoTag::read($reader, 1, $bytePosEnd),
            DefineSoundTag::TYPE => DefineSoundTag::read($reader, $bytePosEnd),
            StartSoundTag::TYPE => StartSoundTag::read($reader),
            DefineButtonSoundTag::TYPE => DefineButtonSoundTag::read($reader),
            SoundStreamHeadTag::TYPE_V1 => SoundStreamHeadTag::read($reader, 1),
            SoundStreamBlockTag::TYPE => SoundStreamBlockTag::read($reader, $bytePosEnd),
            DefineBitsLosslessTag::TYPE_V1 => DefineBitsLosslessTag::read($reader, 1, $bytePosEnd),
            DefineBitsJPEG2Tag::TYPE => DefineBitsJPEG2Tag::read($reader, $bytePosEnd),
            DefineShapeTag::TYPE_V2 => DefineShapeTag::read($reader, 2),
            DefineButtonCxformTag::TYPE => DefineButtonCxformTag::read($reader),
            ProtectTag::TYPE => ProtectTag::read($reader, $bytePosEnd),
            PlaceObject2Tag::TYPE => PlaceObject2Tag::read($reader, $swfVersion),
            RemoveObject2Tag::TYPE => RemoveObject2Tag::read($reader),
            DefineShapeTag::TYPE_V3 => DefineShapeTag::read($reader, 3),
            DefineTextTag::TYPE_V2 => DefineTextTag::read($reader, 2),
            DefineButton2Tag::TYPE => DefineButton2Tag::read($reader, $bytePosEnd),
            DefineBitsJPEG3Tag::TYPE => DefineBitsJPEG3Tag::read($reader, $bytePosEnd),
            DefineBitsLosslessTag::TYPE_V2 => DefineBitsLosslessTag::read($reader, 2, $bytePosEnd),
            DefineEditTextTag::TYPE => DefineEditTextTag::read($reader),
            DefineSpriteTag::TYPE => DefineSpriteTag::read($reader, $swfVersion, $bytePosEnd),
            ProductInfo::TYPE => ProductInfo::read($reader),
            FrameLabelTag::TYPE => FrameLabelTag::read($reader, $bytePosEnd),
            SoundStreamHeadTag::TYPE_V2 => SoundStreamHeadTag::read($reader, 2),
            DefineMorphShapeTag::TYPE => DefineMorphShapeTag::read($reader),
            DefineFont2Or3Tag::TYPE_V2 => DefineFont2Or3Tag::read($reader, 2),
            ExportAssetsTag::ID => ExportAssetsTag::read($reader),
            ImportAssetsTag::TYPE_V1 => ImportAssetsTag::read($reader, 1),
            EnableDebuggerTag::TYPE_V1 => EnableDebuggerTag::read($reader, 1),
            DoInitActionTag::TYPE => DoInitActionTag::read($reader, $bytePosEnd),
            DefineVideoStreamTag::TYPE => DefineVideoStreamTag::read($reader),
            VideoFrameTag::TYPE => VideoFrameTag::read($reader, $bytePosEnd),
            DefineFontInfoTag::TYPE_V2 => DefineFontInfoTag::read($reader, 2, $bytePosEnd),
            EnableDebuggerTag::TYPE_V2 => EnableDebuggerTag::read($reader, 2),
            ScriptLimitsTag::TYPE => ScriptLimitsTag::read($reader),
            SetTabIndexTag::TYPE => SetTabIndexTag::read($reader),
            FileAttributesTag::TYPE => FileAttributesTag::read($reader),
            PlaceObject3Tag::TYPE => PlaceObject3Tag::read($reader, $swfVersion),
            ImportAssetsTag::TYPE_V2 => ImportAssetsTag::read($reader, 2),
            DefineFontAlignZonesTag::TYPE => DefineFontAlignZonesTag::read($reader, $bytePosEnd),
            CSMTextSettingsTag::ID => CSMTextSettingsTag::read($reader),
            DefineFont2Or3Tag::TYPE_V3 => DefineFont2Or3Tag::read($reader, 3),
            SymbolClassTag::TYPE => SymbolClassTag::read($reader),
            MetadataTag::TYPE => MetadataTag::read($reader),
            DefineScalingGridTag::TYPE => DefineScalingGridTag::read($reader),
            DoABCTag::TYPE => DoABCTag::read($reader, $bytePosEnd),
            DefineShape4Tag::TYPE_V4 => DefineShape4Tag::read($reader),
            DefineMorphShape2Tag::TYPE => DefineMorphShape2Tag::read($reader),
            DefineSceneAndFrameLabelDataTag::TYPE => DefineSceneAndFrameLabelDataTag::read($reader),
            DefineBinaryDataTag::TYPE => DefineBinaryDataTag::read($reader, $bytePosEnd),
            DefineFontNameTag::TYPE => DefineFontNameTag::read($reader),
            StartSound2Tag::TYPE => StartSound2Tag::read($reader),
            DefineBitsJPEG4Tag::TYPE => DefineBitsJPEG4Tag::read($reader, $bytePosEnd),
            DefineFont4Tag::TYPE_V4 => DefineFont4Tag::read($reader, $bytePosEnd),
            ReflexTag::TYPE => ReflexTag::read($reader, $bytePosEnd),
            default => UnknownTag::create($reader, $this->type, $bytePosEnd),
        };

        if ($reader->offset < $bytePosEnd && $reader->errors & Errors::EXTRA_DATA) {
            $len = $bytePosEnd - $reader->offset;
            assert($len > 0);

            throw new ParserExtraDataException(
                sprintf('Extra data found after tag %s at offset %d (length = %d)', $this->type, $reader->offset, $len),
                $reader->offset,
                $len
            );
        }

        return $ret;
    }

    /**
     * Read all tags from the SWF file.
     * Stops reading when the end of the file is reached.
     *
     * @param SwfReader $reader The reader to use for reading the tags
     * @param int|null $end The end byte offset to stop reading. If null, it will read until the end of the file.
     * @param bool $parseId If true, the tag id will be parsed and returned. If false, the id will be null.
     *
     * @return iterable<self>
     * @throws ParserExceptionInterface
     */
    public static function readAll(SwfReader $reader, ?int $end = null, bool $parseId = true): iterable
    {
        $end ??= $reader->end;

        while ($reader->offset < $end) {
            $recordHeader = $reader->readUI16();
            $tagType = $recordHeader >> 6;
            $tagLength = $recordHeader & 0x3f;

            if ($tagLength === 0x3f) {
                $tagLength = $reader->readUI32();
            }

            if ($parseId && self::isDefinitionTagType($tagType) && $tagLength >= 2) {
                // The two following bytes are the character id for definition tags
                yield new SwfTag(
                    type: $tagType,
                    offset: $reader->offset,
                    length: $tagLength,
                    id: $reader->readUI16(),
                );
                $reader->skipBytes($tagLength - 2); // 2 bytes already consumed
            } else {
                yield new SwfTag(
                    type: $tagType,
                    offset: $reader->offset,
                    length: $tagLength,
                );
                $reader->skipBytes($tagLength);
            }
        }
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
