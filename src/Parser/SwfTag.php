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
 * SWF.php: Macromedia Flash (SWF) file parser
 * Copyright (C) 2012 Thanos Efraimidis (4real.gr)
 *
 * Arakne-Swf: derived from SWF.php
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Parser;

use Arakne\Swf\Parser\Error\ErrorCollector;
use Arakne\Swf\Parser\Error\TagParseErrorType;
use Arakne\Swf\Parser\Structure\SwfTagPosition;
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

use function substr;

/**
 * Parse SWF tags
 */
final readonly class SwfTag
{
    public function __construct(
        private SwfReader $io,
        private int $swfVersion,
        private ?ErrorCollector $errorCollector,
    ) {}

    public function parseTag(SwfTagPosition $tag): object
    {
        $tagType = $tag->type;
        $tagOffset = $tag->offset;
        $tagLength = $tag->length;

        $this->io->offset = $tagOffset;

        $bytePosEnd = $tagOffset + $tagLength;

        $ret = match ($tagType) {
            EndTag::TYPE => new EndTag(),
            ShowFrameTag::TYPE => new ShowFrameTag(),
            DefineShapeTag::TYPE_V1 => DefineShapeTag::read($this->io, 1),
            PlaceObjectTag::TYPE => PlaceObjectTag::read($this->io, $bytePosEnd),
            RemoveObjectTag::TYPE => RemoveObjectTag::read($this->io),
            DefineBitsTag::TYPE => DefineBitsTag::read($this->io, $bytePosEnd),
            DefineButtonTag::TYPE => DefineButtonTag::read($this->io, $bytePosEnd),
            JPEGTablesTag::TYPE => JPEGTablesTag::read($this->io, $bytePosEnd),
            SetBackgroundColorTag::TYPE => SetBackgroundColorTag::read($this->io),
            DefineFontTag::TYPE_V1 => DefineFontTag::read($this->io),
            DefineTextTag::TYPE_V1 => DefineTextTag::read($this->io, 1),
            DoActionTag::TYPE => DoActionTag::read($this->io, $bytePosEnd),
            DefineFontInfoTag::TYPE_V1 => DefineFontInfoTag::read($this->io, 1, $bytePosEnd),
            DefineSoundTag::TYPE => DefineSoundTag::read($this->io, $bytePosEnd),
            StartSoundTag::TYPE => StartSoundTag::read($this->io),
            DefineButtonSoundTag::TYPE => DefineButtonSoundTag::read($this->io),
            SoundStreamHeadTag::TYPE_V1 => SoundStreamHeadTag::read($this->io, 1),
            SoundStreamBlockTag::TYPE => SoundStreamBlockTag::read($this->io, $bytePosEnd),
            DefineBitsLosslessTag::TYPE_V1 => DefineBitsLosslessTag::read($this->io, 1, $bytePosEnd),
            DefineBitsJPEG2Tag::TYPE => DefineBitsJPEG2Tag::read($this->io, $bytePosEnd),
            DefineShapeTag::TYPE_V2 => DefineShapeTag::read($this->io, 2),
            DefineButtonCxformTag::TYPE => DefineButtonCxformTag::read($this->io),
            ProtectTag::TYPE => ProtectTag::read($this->io, $bytePosEnd),
            PlaceObject2Tag::TYPE => PlaceObject2Tag::read($this->io, $this->swfVersion),
            RemoveObject2Tag::TYPE => RemoveObject2Tag::read($this->io),
            DefineShapeTag::TYPE_V3 => DefineShapeTag::read($this->io, 3),
            DefineTextTag::TYPE_V2 => DefineTextTag::read($this->io, 2),
            DefineButton2Tag::TYPE => DefineButton2Tag::read($this->io, $bytePosEnd),
            DefineBitsJPEG3Tag::TYPE => DefineBitsJPEG3Tag::read($this->io, $bytePosEnd),
            DefineBitsLosslessTag::TYPE_V2 => DefineBitsLosslessTag::read($this->io, 2, $bytePosEnd),
            DefineEditTextTag::TYPE => DefineEditTextTag::read($this->io),
            DefineSpriteTag::TYPE => DefineSpriteTag::read($this->io, $this->swfVersion, $this->errorCollector, $bytePosEnd),
            ProductInfo::TYPE => ProductInfo::read($this->io),
            FrameLabelTag::TYPE => FrameLabelTag::read($this->io, $bytePosEnd),
            SoundStreamHeadTag::TYPE_V2 => SoundStreamHeadTag::read($this->io, 2),
            DefineMorphShapeTag::TYPE => DefineMorphShapeTag::read($this->io),
            DefineFont2Or3Tag::TYPE_V2 => DefineFont2Or3Tag::read($this->io, 2),
            ExportAssetsTag::ID => ExportAssetsTag::read($this->io),
            ImportAssetsTag::TYPE_V1 => ImportAssetsTag::read($this->io, 1),
            EnableDebuggerTag::TYPE_V1 => EnableDebuggerTag::read($this->io, 1),
            DoInitActionTag::TYPE => DoInitActionTag::read($this->io, $bytePosEnd),
            DefineVideoStreamTag::TYPE => DefineVideoStreamTag::read($this->io),
            VideoFrameTag::TYPE => VideoFrameTag::read($this->io, $bytePosEnd),
            DefineFontInfoTag::TYPE_V2 => DefineFontInfoTag::read($this->io, 2, $bytePosEnd),
            EnableDebuggerTag::TYPE_V2 => EnableDebuggerTag::read($this->io, 2),
            ScriptLimitsTag::TYPE => ScriptLimitsTag::read($this->io),
            SetTabIndexTag::TYPE => SetTabIndexTag::read($this->io),
            FileAttributesTag::TYPE => FileAttributesTag::read($this->io),
            PlaceObject3Tag::TYPE => PlaceObject3Tag::read($this->io, $this->swfVersion),
            ImportAssetsTag::TYPE_V2 => ImportAssetsTag::read($this->io, 2),
            DefineFontAlignZonesTag::TYPE => DefineFontAlignZonesTag::read($this->io, $bytePosEnd),
            CSMTextSettingsTag::ID => CSMTextSettingsTag::read($this->io),
            DefineFont2Or3Tag::TYPE_V3 => DefineFont2Or3Tag::read($this->io, 3),
            SymbolClassTag::TYPE => SymbolClassTag::read($this->io),
            MetadataTag::TYPE => MetadataTag::read($this->io),
            DefineScalingGridTag::TYPE => DefineScalingGridTag::read($this->io),
            DoABCTag::TYPE => DoABCTag::read($this->io, $bytePosEnd),
            DefineShape4Tag::TYPE_V4 => DefineShape4Tag::read($this->io),
            DefineMorphShape2Tag::TYPE => DefineMorphShape2Tag::read($this->io),
            DefineSceneAndFrameLabelDataTag::TYPE => DefineSceneAndFrameLabelDataTag::read($this->io),
            DefineBinaryDataTag::TYPE => DefineBinaryDataTag::read($this->io, $bytePosEnd),
            DefineFontNameTag::TYPE => DefineFontNameTag::read($this->io),
            StartSound2Tag::TYPE => StartSound2Tag::read($this->io),
            DefineBitsJPEG4Tag::TYPE => DefineBitsJPEG4Tag::read($this->io, $bytePosEnd),
            DefineFont4Tag::TYPE_V4 => DefineFont4Tag::read($this->io, $bytePosEnd),
            ReflexTag::TYPE => ReflexTag::read($this->io, $bytePosEnd),
            default => new UnknownTag(
                code: $tagType,
                data: $this->io->readBytes($bytePosEnd - $this->io->offset),
            ),
        };

        if ($ret instanceof UnknownTag) {
            $this->errorCollector?->add(
                $tag,
                TagParseErrorType::UnknownTag,
                [
                    'code' => $tagType,
                    'data' => $ret->data,
                ]
            );
        }

        if ($this->io->offset > $bytePosEnd) {
            $this->errorCollector?->add(
                $tag,
                TagParseErrorType::ReadAfterEnd,
                [
                    'length' => $this->io->offset - $bytePosEnd,
                    'data' => substr($this->io->b, $bytePosEnd, $this->io->offset - $bytePosEnd),
                ]
            );
        } elseif ($this->io->offset < $bytePosEnd) {
            $this->errorCollector?->add(
                $tag,
                TagParseErrorType::ExtraBytes,
                [
                    'length' => $bytePosEnd - $this->io->offset,
                    'data' => substr($this->io->b, $this->io->offset, $bytePosEnd - $this->io->offset),
                ]
            );
        }

        return $ret;
    }
}
