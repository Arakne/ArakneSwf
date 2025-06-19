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

use Arakne\Swf\Parser\Error\ErrorCollector;
use Arakne\Swf\Parser\Error\TagParseErrorType;
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

use function strlen;
use function substr;

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
     * Parse the tag structure
     *
     * @param SwfReader $reader The base reader to use for parsing
     * @param non-negative-int $swfVersion The SWF version of the file being parsed
     * @param ErrorCollector|null $errorCollector @todo deprecated, to remove
     *
     * @return object
     */
    public function parse(SwfReader $reader, int $swfVersion, ?ErrorCollector $errorCollector): object
    {
        // @todo clone reader and handle its length
        $reader->offset = $this->offset;
        $bytePosEnd = $this->offset + $this->length;

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
            DefineSpriteTag::TYPE => DefineSpriteTag::read($reader, $swfVersion, $errorCollector, $bytePosEnd),
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
            default => new UnknownTag(
                code: $this->type,
                data: $reader->readBytes(max($bytePosEnd - $reader->offset, 0)),
            ),
        };

        if ($ret instanceof UnknownTag) {
            $errorCollector?->add(
                $this,
                TagParseErrorType::UnknownTag,
                [
                    'code' => $this->type,
                    'data' => $ret->data,
                ]
            );
        }

        if ($reader->offset > $bytePosEnd) {
            $errorCollector?->add(
                $this,
                TagParseErrorType::ReadAfterEnd,
                [
                    'length' => $reader->offset - $bytePosEnd,
                    'data' => substr($reader->b, $bytePosEnd, $reader->offset - $bytePosEnd),
                ]
            );
        } elseif ($reader->offset < $bytePosEnd) {
            $errorCollector?->add(
                $this,
                TagParseErrorType::ExtraBytes,
                [
                    'length' => $bytePosEnd - $reader->offset,
                    'data' => substr($reader->b, $reader->offset, $bytePosEnd - $reader->offset),
                ]
            );
        }

        return $ret;
    }

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
                $tags[] = new SwfTag(
                    type: $tagType,
                    offset: $reader->offset,
                    length: $tagLength,
                    id: $reader->readUI16(),
                );
                $reader->skipBytes($tagLength - 2); // 2 bytes already consumed
            } else {
                $tags[] = new SwfTag(
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
