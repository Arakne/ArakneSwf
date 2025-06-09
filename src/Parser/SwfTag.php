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
use Arakne\Swf\Parser\Structure\Action\ActionRecord;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
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
use Exception;
use http\Exception\RuntimeException;

use function assert;
use function sprintf;
use function strlen;
use function substr;

/**
 * Parse SWF tags
 */
final readonly class SwfTag
{
    public function __construct(
        private SwfReader $io,
        private SwfRec $rec,
        private int $swfVersion,
        private ?ErrorCollector $errorCollector,
    ) {}

    /**
     * Get list of tags positions
     * To extract actual tag data, use {@see SwfTag::parseTag()} on each tag
     *
     * @return list<SwfTagPosition>
     */
    public function parseTags(): array
    {
        $tags = [];
        $io = $this->io;
        $len = strlen($io->b);

        while ($io->offset < $len) {
            // Collect record header (short or long)
            $recordHeader = $io->readUI16();
            $tagType = $recordHeader >> 6;
            $tagLength = $recordHeader & 0x3f;

            if ($tagLength === 0x3f) {
                $tagLength = $io->readUI32();
            }

            // For definition tags, collect the 'id' also
            if ($this->isDefinitionTagType($tagType)) {
                assert($tagLength >= 2);
                $tags[] = new SwfTagPosition(
                    type: $tagType,
                    offset: $io->offset,
                    length: $tagLength,
                    id: $io->readUI16(),
                );
                $io->skipBytes($tagLength - 2); // 2 bytes already consumed
            } else {
                $tags[] = new SwfTagPosition(
                    type: $tagType,
                    offset: $io->offset,
                    length: $tagLength,
                );
                $io->skipBytes($tagLength);
            }
        }

        return $tags;
    }

    private function isDefinitionTagType(int $tagType): bool
    {
        switch ($tagType) {
            case 2: // DefineShape
            case 22: // DefineShape2
            case 32: // DefineShape3
            case 83: // DefineShape4
                return true; // shapeId
            case 10: // DefineFont
            case 48: // DefineFont2
            case 75: // DefineFont3
            case 91: // DefineFont4
                return true; // fontId
            case 7: // DefineButton
            case 34: // DefineButton2
                return true; // buttonId
            case 14: // DefineSound
                return true; // soundId
            case 39: // DefineSprite
                return true; // spriteId
            case 11: // DefineText
            case 33: // DefineText2
            case 20: // DefineBitsLossless
            case 36: // DefineBitsLossless2
            case 6: // DefineBits
            case 21: // DefineBitsJPEG2
            case 35: // DefineBitsJPEG3
            case 90: // DefineBitsJPEG4
            case 37: // DefineEditText
            case 46: // DefineMorphShape
            case 84: // DefineMorphShape2
            case 60: // DefineVideoStream
                return true; // characterId
        }
        return false;
    }

    public function parseTag(SwfTagPosition $tag): object
    {
        $tagType = $tag->type;
        $tagOffset = $tag->offset;
        $tagLength = $tag->length;

        $this->io->offset = $tagOffset;

        $bytePosEnd = $tagOffset + $tagLength;

        $ret = match ($tagType) {
            EndTag::TYPE => $this->parseEndTag($bytePosEnd),
            ShowFrameTag::TYPE => $this->parseShowFrameTag($bytePosEnd),
            DefineShapeTag::TYPE_V1 => $this->parseDefineShapeTag($bytePosEnd, 1),
            PlaceObjectTag::TYPE => $this->parsePlaceObjectTag($bytePosEnd),
            RemoveObjectTag::TYPE => $this->parseRemoveObjectTag($bytePosEnd, 1),
            6 => $this->parseDefineBitsTag($bytePosEnd),
            7 => $this->parseDefineButtonTag($bytePosEnd, 1),
            8 => $this->parseJPEGTablesTag($bytePosEnd),
            9 => $this->parseSetBackgroundColorTag($bytePosEnd),
            10 => $this->parseDefineFontTag($bytePosEnd),
            11 => $this->parseDefineTextTag($bytePosEnd, 1),
            DoActionTag::TYPE => $this->parseDoActionTag($bytePosEnd),
            13 => $this->parseDefineFontInfoTag($bytePosEnd, 1),
            14 => $this->parseDefineSoundTag($bytePosEnd),
            15 => $this->parseStartSoundTag($bytePosEnd, 1),
            17 => $this->parseDefineButtonSoundTag($bytePosEnd),
            18 => $this->parseSoundStreamHeadTag($bytePosEnd, 1),
            19 => $this->parseSoundStreamBlockTag($bytePosEnd),
            20 => $this->parseDefineBitsLosslessTag($bytePosEnd, 1),
            21 => $this->parseDefineBitsJPEGTag($bytePosEnd, 2),
            DefineShapeTag::TYPE_V2 => $this->parseDefineShapeTag($bytePosEnd, 2),
            23 => $this->parseDefineButtonCxformTag($bytePosEnd),
            24 => $this->parseProtectTag($bytePosEnd),
            PlaceObject2Tag::TYPE => $this->parsePlaceObject2Tag($bytePosEnd, $this->swfVersion),
            RemoveObject2Tag::TYPE => $this->parseRemoveObjectTag($bytePosEnd, 2),
            DefineShapeTag::TYPE_V3 => $this->parseDefineShapeTag($bytePosEnd, 3),
            33 => $this->parseDefineTextTag($bytePosEnd, 2),
            34 => $this->parseDefineButtonTag($bytePosEnd, 2),
            35 => $this->parseDefineBitsJPEGTag($bytePosEnd, 3),
            36 => $this->parseDefineBitsLosslessTag($bytePosEnd, 2),
            37 => $this->parseDefineEditTextTag($bytePosEnd),
            DefineSpriteTag::TYPE => $this->parseDefineSpriteTag($bytePosEnd),
            41 => $this->parseProductInfoTag($bytePosEnd),
            FrameLabelTag::TYPE => $this->parseFrameLabelTag($bytePosEnd),
            45 => $this->parseSoundStreamHeadTag($bytePosEnd, 2),
            46 => $this->parseDefineMorphShapeTag($bytePosEnd, 1),
            48 => $this->parseDefineFont23Tag($bytePosEnd, 2),
            56 => $this->parseExportAssetsTag($bytePosEnd),
            57 => $this->parseImportAssetsTag($bytePosEnd, 1),
            58 => $this->parseEnableDebuggerTag($bytePosEnd, 1),
            59 => $this->parseDoInitActionTag($bytePosEnd),
            60 => $this->parseDefineVideoStreamTag($bytePosEnd),
            61 => $this->parseVideoFrameTag($bytePosEnd),
            62 => $this->parseDefineFontInfoTag($bytePosEnd, 2),
            64 => $this->parseEnableDebuggerTag($bytePosEnd, 2),
            65 => $this->parseScriptLimitsTag($bytePosEnd),
            66 => $this->parseSetTabIndexTag($bytePosEnd),
            69 => $this->parseFileAttributesTag($bytePosEnd),
            PlaceObject3Tag::TYPE => $this->parsePlaceObject3Tag($bytePosEnd),
            71 => $this->parseImportAssetsTag($bytePosEnd, 2),
            73 => $this->parseDefineFontAlignZonesTag($bytePosEnd),
            74 => $this->parseCSMTextSettingsTag($bytePosEnd),
            75 => $this->parseDefineFont23Tag($bytePosEnd, 3),
            76 => $this->parseSymbolClassTag($bytePosEnd),
            77 => $this->parseMetadataTag($bytePosEnd),
            78 => $this->parseDefineScalingGridTag($bytePosEnd),
            82 => $this->parseDoAbcTag($bytePosEnd),
            DefineShape4Tag::TYPE_V4 => $this->parseDefineShapeTag($bytePosEnd, 4),
            84 => $this->parseDefineMorphShapeTag($bytePosEnd, 2),
            86 => $this->parseDefineSceneAndFrameLabelDataTag($bytePosEnd),
            87 => $this->parseDefineBinaryDataTag($bytePosEnd),
            88 => $this->parseDefineFontNameTag($bytePosEnd),
            89 => $this->parseStartSoundTag($bytePosEnd, 2),
            90 => $this->parseDefineBitsJPEGTag($bytePosEnd, 4),
            91 => $this->parseDefineFont4Tag($bytePosEnd),
            777 => new ReflexTag($this->io->readBytes($bytePosEnd - $this->io->offset)),
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

    private function parseEndTag(int $bytePosEnd): EndTag
    {
        return new EndTag();
    }

    private function parseShowFrameTag(int $bytePosEnd): ShowFrameTag
    {
        return new ShowFrameTag();
    }

    private function parseDefineShapeTag(int $bytePosEnd, int $shapeVersion): DefineShapeTag|DefineShape4Tag
    {
        if ($shapeVersion < 4) {
            return new DefineShapeTag(
                version: $shapeVersion,
                shapeId: $this->io->readUI16(),
                shapeBounds: Rectangle::read($this->io),
                shapes: $this->rec->collectShapeWithStyle($shapeVersion),
            );
        }

        return new DefineShape4Tag(
            shapeId: $this->io->readUI16(),
            shapeBounds: Rectangle::read($this->io),
            edgeBounds: Rectangle::read($this->io),
            reserved: $this->io->readUB(5),
            usesFillWindingRule: $this->io->readBool(),
            usesNonScalingStrokes: $this->io->readBool(),
            usesScalingStrokes: $this->io->readBool(),
            shapes: $this->rec->collectShapeWithStyle($shapeVersion),
        );
    }

    private function parsePlaceObjectTag(int $bytePosEnd): PlaceObjectTag
    {
        return new PlaceObjectTag(
            characterId: $this->io->readUI16(),
            depth: $this->io->readUI16(),
            matrix: Matrix::read($this->io),
            colorTransform: $this->io->offset < $bytePosEnd ? ColorTransform::read($this->io, false) : null,
        );
    }

    private function parseRemoveObjectTag(int $bytePosEnd, int $version): RemoveObjectTag|RemoveObject2Tag
    {
        assert($version === 1 || $version === 2);

        return match ($version) {
            1 => new RemoveObjectTag(
                characterId: $this->io->readUI16(),
                depth: $this->io->readUI16(),
            ),
            2 => new RemoveObject2Tag(
                depth: $this->io->readUI16(),
            ),
        };
    }

    private function parseDefineBitsTag(int $bytePosEnd): DefineBitsTag
    {
        return new DefineBitsTag(
            characterId: $this->io->readUI16(),
            imageData: $this->io->readBytes($bytePosEnd - $this->io->offset),
        );
    }

    private function parseDefineButtonTag(int $bytePosEnd, int $version): DefineButtonTag|DefineButton2Tag
    {
        if ($version === 1) {
            return new DefineButtonTag(
                buttonId: $this->io->readUI16(),
                characters: $this->rec->collectButtonRecords($version),
                actions: ActionRecord::readCollection($this->io, $bytePosEnd),
            );
        }

        $buttonId = $this->io->readUI16();
        $this->io->skipBits(7); // Reserved, must be 0
        $taskAsMenu = $this->io->readBool();
        $actionOffset = $this->io->readUI16();
        $characters = $this->rec->collectButtonRecords($version);
        $actions = $actionOffset !== 0 ? $this->rec->collectButtonCondActions($bytePosEnd) : [];

        return new DefineButton2Tag(
            buttonId: $buttonId,
            trackAsMenu: $taskAsMenu,
            actionOffset: $actionOffset,
            characters: $characters,
            actions: $actions,
        );
    }

    private function parseJPEGTablesTag(int $bytePosEnd): JPEGTablesTag
    {
        return new JPEGTablesTag($this->io->readBytes($bytePosEnd - $this->io->offset));
    }

    private function parseSetBackgroundColorTag(int $bytePosEnd): SetBackgroundColorTag
    {
        return new SetBackgroundColorTag(
            red: $this->io->readUI8(),
            green: $this->io->readUI8(),
            blue: $this->io->readUI8(),
        );
    }

    private function parseDefineFontTag(int $bytePosEnd): DefineFontTag
    {
        $fontId = $this->io->readUI16();
        // Collect and push back 1st element of OffsetTable (this is numGlyphs * 2)
        $numGlyphs = $this->io->readUI16() / 2;
        $this->io->offset -= 2;
        $offsetTable = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            $offsetTable[] = $this->io->readUI16();
        }
        $glyphShapeData = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            $glyphShapeData[] = $this->rec->collectShape(1);
        }

        return new DefineFontTag(
            fontId: $fontId,
            offsetTable: $offsetTable,
            glyphShapeData: $glyphShapeData,
        );
    }

    private function parseDefineTextTag(int $bytePosEnd, int $textVersion): DefineTextTag
    {
        return new DefineTextTag(
            version: $textVersion,
            characterId: $this->io->readUI16(),
            textBounds: Rectangle::read($this->io),
            textMatrix: Matrix::read($this->io),
            glyphBits: $glyphBits = $this->io->readUI8(),
            advanceBits: $advanceBits = $this->io->readUI8(),
            textRecords: $this->rec->collectTextRecords($glyphBits, $advanceBits, $textVersion),
        );
    }

    private function parseDoActionTag(int $bytePosEnd): DoActionTag
    {
        return new DoActionTag(ActionRecord::readCollection($this->io, $bytePosEnd));
    }

    private function parseDefineFontInfoTag(int $bytePosEnd, int $version): DefineFontInfoTag
    {
        $fontId = $this->io->readUI16();
        $fontNameLen = $this->io->readUI8();
        $fontName = $this->io->readBytes($fontNameLen);

        $this->io->skipBits(2); // Reserved
        $fontFlagsSmallText = $this->io->readBool();
        $fontFlagsShiftJIS = $this->io->readBool();
        $fontFlagsANSI = $this->io->readBool();
        $fontFlagsItalic = $this->io->readBool();
        $fontFlagsBold = $this->io->readBool();
        $fontFlagsWideCodes = $this->io->readBool();
        $languageCode = null;
        $codeTable = [];

        if ($version === 1) {
            while ($this->io->offset < $bytePosEnd) {
                $codeTable[] = $fontFlagsWideCodes ? $this->io->readUI16() : $this->io->readUI8();
            }
        } elseif ($version === 2) {
            $languageCode = $this->io->readUI8();

            while ($this->io->offset < $bytePosEnd) {
                $codeTable[] = $this->io->readUI16();
            }
        }

        return new DefineFontInfoTag(
            version: $version,
            fontId: $fontId,
            fontName: $fontName,
            fontFlagsSmallText: $fontFlagsSmallText,
            fontFlagsShiftJIS: $fontFlagsShiftJIS,
            fontFlagsANSI: $fontFlagsANSI,
            fontFlagsItalic: $fontFlagsItalic,
            fontFlagsBold: $fontFlagsBold,
            fontFlagsWideCodes: $fontFlagsWideCodes,
            codeTable: $codeTable,
            languageCode: $languageCode,
        );
    }

    private function parseDefineSoundTag(int $bytePosEnd): DefineSoundTag
    {
        return new DefineSoundTag(
            soundId: $this->io->readUI16(),
            soundFormat: $this->io->readUB(4),
            soundRate: $this->io->readUB(2),
            soundSize: $this->io->readBool() ? 0 : 1,
            soundType: $this->io->readBool() ? 0 : 1,
            soundSampleCount: $this->io->readUI32(),
            soundData: $this->io->readBytes($bytePosEnd - $this->io->offset),
        );
    }

    private function parseStartSoundTag(int $bytePosEnd, int $version): StartSoundTag|StartSound2Tag
    {
        assert($version === 1 || $version === 2);

        return match ($version) {
            1 => new StartSoundTag(
                soundId: $this->io->readUI16(),
                soundInfo: $this->rec->collectSoundInfo(),
            ),
            2 => new StartSound2Tag(
                soundClassName: $this->io->readNullTerminatedString(),
                soundInfo: $this->rec->collectSoundInfo(),
            ),
        };
    }

    private function parseDefineButtonSoundTag(int $bytePosEnd): DefineButtonSoundTag
    {
        return new DefineButtonSoundTag(
            buttonId: $this->io->readUI16(),
            buttonSoundChar0: $char0 = $this->io->readUI16(),
            buttonSoundInfo0: $char0 !== 0 ? $this->rec->collectSoundInfo() : null,
            buttonSoundChar1: $char1 = $this->io->readUI16(),
            buttonSoundInfo1: $char1 !== 0 ? $this->rec->collectSoundInfo() : null,
            buttonSoundChar2: $char2 = $this->io->readUI16(),
            buttonSoundInfo2: $char2 !== 0 ? $this->rec->collectSoundInfo() : null,
            buttonSoundChar3: $char3 = $this->io->readUI16(),
            buttonSoundInfo3: $char3 !== 0 ? $this->rec->collectSoundInfo() : null,
        );
    }

    private function parseSoundStreamHeadTag(int $bytePosEnd, int $version): SoundStreamHeadTag
    {
        $this->io->skipBits(4); // Reserved

        $playbackSoundRate = $this->io->readUB(2);
        $playbackSoundSize = $this->io->readBool() ? 0 : 1; // @todo rename to 16bits bool
        $playbackSoundType = $this->io->readBool() ? 0 : 1; // @todo rename to stereo bool

        $streamSoundCompression = $this->io->readUB(4);
        $streamSoundRate = $this->io->readUB(2);
        $streamSoundSize = $this->io->readBool() ? 0 : 1;
        $streamSoundType = $this->io->readBool() ? 0 : 1;
        $streamSoundSampleCount = $this->io->readUI16();

        $latencySeek = $streamSoundSampleCount === 2 ? $this->io->readSI16() : null; // MP3

        return new SoundStreamHeadTag(
            version: $version,
            playbackSoundRate: $playbackSoundRate,
            playbackSoundSize: $playbackSoundSize,
            playbackSoundType: $playbackSoundType,
            streamSoundCompression: $streamSoundCompression,
            streamSoundRate: $streamSoundRate,
            streamSoundSize: $streamSoundSize,
            streamSoundType: $streamSoundType,
            streamSoundSampleCount: $streamSoundSampleCount,
            latencySeek: $latencySeek,
        );
    }

    private function parseSoundStreamBlockTag(int $bytePosEnd): SoundStreamBlockTag
    {
        return new SoundStreamBlockTag($this->io->readBytes($bytePosEnd - $this->io->offset));
    }

    private function parseDefineBitsLosslessTag(int $bytePosEnd, int $version): DefineBitsLosslessTag
    {
        assert($version === 1 || $version === 2);

        $characterId = $this->io->readUI16();
        $bitmapFormat = $this->io->readUI8();
        $bitmapWidth = $this->io->readUI16();
        $bitmapHeight = $this->io->readUI16();

        if ($bitmapFormat == 3) {
            $colors = $this->io->readUI8();
            $data = gzuncompress($this->io->readBytes($bytePosEnd - $this->io->offset)) ?: throw new RuntimeException(sprintf('Invalid ZLIB data'));
            $colorTableSize = match ($version) {
                1 => 3 * ($colors + 1), // 3 bytes per RGB value
                2 => 4 * ($colors + 1), // 4 bytes per RGBA value
            };

            $colorTable = substr($data, 0, $colorTableSize);
            $pixelData = substr($data, $colorTableSize);
        } elseif ($bitmapFormat == 4 || $bitmapFormat == 5) {
            $colorTable = null;
            $pixelData = gzuncompress($this->io->readBytes($bytePosEnd - $this->io->offset)) ?: throw new RuntimeException(sprintf('Invalid ZLIB data'));
        } else {
            throw new Exception(sprintf('Internal error: bitmapFormat=%d', $bitmapFormat));
        }

        return new DefineBitsLosslessTag(
            version: $version,
            characterId: $characterId,
            bitmapFormat: $bitmapFormat,
            bitmapWidth: $bitmapWidth,
            bitmapHeight: $bitmapHeight,
            colorTable: $colorTable,
            pixelData: $pixelData,
        );
    }

    /**
     * @param int $bytePosEnd
     * @param 2|3|4 $version
     * @return DefineBitsJPEG2Tag|DefineBitsJPEG3Tag|DefineBitsJPEG4Tag
     */
    private function parseDefineBitsJPEGTag(int $bytePosEnd, int $version): DefineBitsJPEG2Tag|DefineBitsJPEG3Tag|DefineBitsJPEG4Tag
    {
        assert($version === 2 || $version === 3 || $version === 4);

        switch ($version) {
            case 2:
                return new DefineBitsJPEG2Tag(
                    characterId: $this->io->readUI16(),
                    imageData: $this->io->readBytes($bytePosEnd - $this->io->offset),
                );

            case 3:
                return new DefineBitsJPEG3Tag(
                    characterId: $this->io->readUI16(),
                    imageData: $this->io->readBytes($this->io->readUI32()),
                    alphaData: gzuncompress($this->io->readBytes($bytePosEnd - $this->io->offset)) ?: throw new RuntimeException(sprintf('Invalid ZLIB data')), // ZLIB uncompress alpha channel
                );

            case 4:
                $characterId = $this->io->readUI16();
                $alphaDataOffset = $this->io->readUI32();
                $deblockParam = $this->io->readUI16();
                $imageData = $this->io->readBytes($alphaDataOffset);
                $alphaData = gzuncompress($this->io->readBytes($bytePosEnd - $this->io->offset)) ?: throw new RuntimeException(sprintf('Invalid ZLIB data')); // ZLIB uncompress alpha channel

                return new DefineBitsJPEG4Tag(
                    characterId: $characterId,
                    deblockParam: $deblockParam,
                    imageData: $imageData,
                    alphaData: $alphaData,
                );
        }
    }

    private function parseDefineButtonCxformTag(int $bytePosEnd): DefineButtonCxformTag
    {
        return new DefineButtonCxformTag(
            buttonId: $this->io->readUI16(),
            colorTransform: ColorTransform::read($this->io, false),
        );
    }

    private function parseProtectTag(int $bytePosEnd): ProtectTag
    {
        return new ProtectTag(
            // Password is only present if tag length is not 0
            // It's stored as a null-terminated string
            password: $bytePosEnd > $this->io->offset ? $this->io->readNullTerminatedString() : null,
        );
    }

    private function parsePlaceObject2Tag(int $bytePosEnd, int $swfVersion): PlaceObject2Tag
    {
        $placeFlagHasClipActions = $this->io->readBool();
        $placeFlagHasClipDepth = $this->io->readBool();
        $placeFlagHasName = $this->io->readBool();
        $placeFlagHasRatio = $this->io->readBool();
        $placeFlagHasColorTransform = $this->io->readBool();
        $placeFlagHasMatrix = $this->io->readBool();
        $placeFlagHasCharacter = $this->io->readBool();
        $placeFlagMove = $this->io->readBool();

        return new PlaceObject2Tag(
            move: $placeFlagMove,
            depth: $this->io->readUI16(),
            characterId: $placeFlagHasCharacter ? $this->io->readUI16() : null,
            matrix: $placeFlagHasMatrix ? Matrix::read($this->io) : null,
            colorTransform: $placeFlagHasColorTransform ? ColorTransform::read($this->io, true) : null,
            ratio: $placeFlagHasRatio ? $this->io->readUI16() : null,
            name: $placeFlagHasName ? $this->io->readNullTerminatedString() : null,
            clipDepth: $placeFlagHasClipDepth ? $this->io->readUI16() : null,
            clipActions: $placeFlagHasClipActions ? $this->rec->collectClipActions($swfVersion) : null,
        );
    }

    private function parseDefineEditTextTag(int $bytePosEnd): DefineEditTextTag
    {
        $characterId = $this->io->readUI16();
        $bounds = Rectangle::read($this->io);

        $flags = $this->io->readUI8();
        $hasText = ($flags & 0b10000000) === 0b10000000;
        $wordWrap = ($flags & 0b01000000) === 0b01000000;
        $multiline = ($flags & 0b00100000) === 0b00100000;
        $password = ($flags & 0b00010000) === 0b00010000;
        $readOnly = ($flags & 0b00001000) === 0b00001000;
        $hasTextColor = ($flags & 0b00000100) === 0b00000100;
        $hasMaxLength = ($flags & 0b00000010) === 0b00000010;
        $hasFont = ($flags & 0b00000001) === 0b00000001;

        $flags = $this->io->readUI8();
        $hasFontClass = ($flags & 0b10000000) === 0b10000000;
        $autoSize = ($flags & 0b01000000) === 0b01000000;
        $hasLayout = ($flags & 0b00100000) === 0b00100000;
        $noSelect = ($flags & 0b00010000) === 0b00010000;
        $border = ($flags & 0b00001000) === 0b00001000;
        $wasStatic = ($flags & 0b00000100) === 0b00000100;
        $html = ($flags & 0b00000010) === 0b00000010;
        $useOutlines = ($flags & 0b00000001) === 0b00000001;

        return new DefineEditTextTag(
            characterId: $characterId,
            bounds: $bounds,
            wordWrap: $wordWrap,
            multiline: $multiline,
            password: $password,
            readOnly: $readOnly,
            autoSize: $autoSize,
            noSelect: $noSelect,
            border: $border,
            wasStatic: $wasStatic,
            html: $html,
            useOutlines: $useOutlines,
            fontId: $hasFont ? $this->io->readUI16() : null,
            fontClass: $hasFontClass ? $this->io->readNullTerminatedString() : null,
            fontHeight: $hasFont ? $this->io->readUI16() : null,
            textColor: $hasTextColor ? Color::readRgba($this->io) : null,
            maxLength: $hasMaxLength ? $this->io->readUI16() : null,
            layout: $hasLayout ? [
                'align' => $this->io->readUI8(),
                'leftMargin' => $this->io->readUI16(),
                'rightMargin' => $this->io->readUI16(),
                'indent' => $this->io->readUI16(),
                'leading' => $this->io->readSI16(),
            ] : null,
            variableName: $this->io->readNullTerminatedString(),
            initialText: $hasText ? $this->io->readNullTerminatedString() : null,
        );
    }

    private function parseDefineSpriteTag(int $bytePosEnd): DefineSpriteTag
    {
        $spriteId = $this->io->readUI16();
        $frameCount = $this->io->readUI16();
        $b = $this->io->readBytes($bytePosEnd - $this->io->offset);

        $io = new SwfReader($b);
        $rec = new SwfRec($io);
        $tag = new SwfTag($io, $rec, $this->swfVersion, $this->errorCollector);

        // Collect and parse tags
        $tags = [];

        while ($io->offset < strlen($io->b)) {
            $recordHeader = $io->readUI16();
            $tagType = $recordHeader >> 6;
            $tagLength = $recordHeader & 0x3f;
            if ($tagLength == 0x3f) {
                $tagLength = $io->readSI32();
            }
            $bytePosEnd = $io->offset + $tagLength;
            $tags[] = $tag->parseTag(new SwfTagPosition($tagType, $io->offset, $tagLength));
            $io->offset = $bytePosEnd;
        }

        return new DefineSpriteTag(
            spriteId: $spriteId,
            frameCount: $frameCount,
            tags: $tags,
        );
    }

    private function parseFrameLabelTag(int $bytePosEnd): FrameLabelTag
    {
        // Parse null-terminated string
        $label = $this->io->readNullTerminatedString();

        // Since SWF 6, the flag namedAnchor is present to create a named anchor
        // So we need to check if there is still data to read, and if so, read the flag
        $hasMoreData = $this->io->offset < $bytePosEnd;

        return new FrameLabelTag(
            $label,
            $hasMoreData && $this->io->readUI8() === 1,
        );
    }

    private function parseDefineMorphShapeTag(int $bytePosEnd, int $version): DefineMorphShapeTag|DefineMorphShape2Tag
    {
        $characterId = $this->io->readUI16();
        $startBounds = Rectangle::read($this->io);
        $endBounds = Rectangle::read($this->io);

        if ($version === 1) {
            return new DefineMorphShapeTag(
                characterId: $characterId,
                startBounds: $startBounds,
                endBounds: $endBounds,
                offset: $this->io->readUI32(),
                fillStyles: $this->rec->collectMorphFillStyleArray(),
                lineStyles: $this->rec->collectMorphLineStyleArray(1),
                startEdges: $this->rec->collectShape(1),
                endEdges: $this->rec->collectShape(1),
            );
        }

        $startEdgeBounds = Rectangle::read($this->io);
        $endEdgeBounds = Rectangle::read($this->io);
        $this->io->skipBits(6); // Reserved
        $usesNonScalingStrokes = $this->io->readBool();
        $usesScalingStrokes = $this->io->readBool();

        return new DefineMorphShape2Tag(
            characterId: $characterId,
            startBounds: $startBounds,
            endBounds: $endBounds,
            startEdgeBounds: $startEdgeBounds,
            endEdgeBounds: $endEdgeBounds,
            usesNonScalingStrokes: $usesNonScalingStrokes,
            usesScalingStrokes: $usesScalingStrokes,
            offset: $this->io->readUI32(),
            fillStyles: $this->rec->collectMorphFillStyleArray(),
            lineStyles: $this->rec->collectMorphLineStyleArray(2),
            startEdges: $this->rec->collectShape(1), // @todo version ?
            endEdges: $this->rec->collectShape(1), // @todo version ?
        );
    }

    private function parseDefineFont23Tag(int $bytePosEnd, int $version): DefineFont2Or3Tag
    {
        $fontId = $this->io->readUI16();

        $flags = $this->io->readUI8();
        $fontFlagsHasLayout = ($flags & 0b10000000) === 0b10000000;
        $fontFlagsShiftJIS = ($flags & 0b01000000) === 0b01000000;
        $fontFlagsSmallText = ($flags & 0b00100000) === 0b00100000;
        $fontFlagsANSI = ($flags & 0b00010000) === 0b00010000;
        $fontFlagsWideOffsets = ($flags & 0b00001000) === 0b00001000;
        $fontFlagsWideCodes = ($flags & 0b00000100) === 0b00000100;
        $fontFlagsItalic = ($flags & 0b00000010) === 0b00000010;
        $fontFlagsBold = ($flags & 0b00000001) === 0b00000001;

        $languageCode = $this->io->readUI8();
        $fontNameLength = $this->io->readUI8();
        $fontName = substr($this->io->readBytes($fontNameLength), 0, -1); // Remove trailing NULL
        $numGlyphs = $this->io->readUI16();

        $offsetTable = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            $offsetTable[] = $fontFlagsWideOffsets ? $this->io->readUI32() : $this->io->readUI16();
        }

        $codeTableOffset = $fontFlagsWideOffsets ? $this->io->readUI32() : $this->io->readUI16();

        $glyphShapeTable = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            $glyphShapeTable[] = $this->rec->collectShape(1);
        }

        $codeTable = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            if ($version === 2) {
                $codeTable[] = $fontFlagsWideCodes ? $this->io->readUI16() : $this->io->readUI8();
            } elseif ($version === 3) {
                $codeTable[] = $this->io->readUI16();
            }
        }

        if ($fontFlagsHasLayout) {
            $layout = [];
            $layout['fontAscent'] = $this->io->readSI16();
            $layout['fontDescent'] = $this->io->readSI16();
            $layout['fontLeading'] = $this->io->readSI16();
            $layout['fontAdvanceTable'] = [];
            for ($i = 0; $i < $numGlyphs; $i++) {
                $layout['fontAdvanceTable'][] = $this->io->readSI16();
            }
            $layout['fontBoundsTable'] = [];
            for ($i = 0; $i < $numGlyphs; $i++) {
                $layout['fontBoundsTable'][] = Rectangle::read($this->io);
            }
            $kerningCount = $this->io->readUI16();
            $layout['fontKerningTable'] = [];
            for ($i = 0; $i < $kerningCount; $i++) {
                $fontKerningCode1 = $fontFlagsWideCodes ? $this->io->readUI16() : $this->io->readUI8();
                $fontKerningCode2 = $fontFlagsWideCodes ? $this->io->readUI16() : $this->io->readUI8();
                $fontKerningAdjustment = $this->io->readSI16();
                $layout['fontKerningTable'][] = [
                    'fontKerningCode1' => $fontKerningCode1,
                    'fontKerningCode2' => $fontKerningCode2,
                    'fontKerningAdjustment' => $fontKerningAdjustment,
                ];
            }
        } else {
            $layout = null;
        }

        return new DefineFont2Or3Tag(
            $version,
            $fontId,
            $fontFlagsHasLayout,
            $fontFlagsShiftJIS,
            $fontFlagsSmallText,
            $fontFlagsANSI,
            $fontFlagsWideOffsets,
            $fontFlagsWideCodes,
            $fontFlagsItalic,
            $fontFlagsBold,
            $languageCode,
            $fontName,
            $numGlyphs,
            $offsetTable,
            $glyphShapeTable,
            $codeTable,
            $layout
        );
    }

    private function parseExportAssetsTag(int $bytePosEnd): ExportAssetsTag
    {
        $tags = [];
        $names = [];
        $count = $this->io->readUI16();

        for ($i = 0; $i < $count; $i++) {
            $tags[] = $this->io->readUI16();
            $names[] = $this->io->readNullTerminatedString();
        }

        return new ExportAssetsTag(
            tags: $tags,
            names: $names,
        );
    }

    private function parseImportAssetsTag(int $bytePosEnd, int $version): ImportAssetsTag
    {
        $url = $this->io->readNullTerminatedString();

        if ($version === 2) {
            $this->io->skipBytes(1); // Reserved, must be 1
            $this->io->skipBytes(1); // Reserved, must be 0
        }

        $tags = [];
        $names = [];
        $count = $this->io->readUI16();

        for ($i = 0; $i < $count; $i++) {
            $tags[] = $this->io->readUI16();
            $names[] = $this->io->readNullTerminatedString();
        }

        return new ImportAssetsTag(
            version: $version,
            url: $url,
            tags: $tags,
            names: $names,
        );
    }

    private function parseEnableDebuggerTag(int $bytePosEnd, int $version): EnableDebuggerTag
    {
        if ($version === 2) {
            $this->io->skipBytes(2); // Reserved, must be 0
        }

        return new EnableDebuggerTag(
            version: $version,
            password: $this->io->readNullTerminatedString(),
        );
    }

    private function parseDoInitActionTag(int $bytePosEnd): DoInitActionTag
    {
        return new DoInitActionTag(
            spriteId: $this->io->readUI16(),
            actions: ActionRecord::readCollection($this->io, $bytePosEnd),
        );
    }

    private function parseDefineVideoStreamTag(int $bytePosEnd): DefineVideoStreamTag
    {
        $characterId = $this->io->readUI16();
        $numFrames = $this->io->readUI16();
        $width = $this->io->readUI16();
        $height = $this->io->readUI16();

        $this->io->skipBits(4); // Reserved
        $videoFlagsDeblocking = $this->io->readUB(3);
        $videoFlagsSmoothing = $this->io->readBool();

        $codecId = $this->io->readUI8();

        return new DefineVideoStreamTag(
            characterId: $characterId,
            numFrames: $numFrames,
            width: $width,
            height: $height,
            deblocking: $videoFlagsDeblocking,
            smoothing: $videoFlagsSmoothing,
            codecId: $codecId,
        );
    }

    private function parseVideoFrameTag(int $bytePosEnd): VideoFrameTag
    {
        return new VideoFrameTag(
            streamId: $this->io->readUI16(),
            frameNum: $this->io->readUI16(),
            videoData: $this->io->readBytes($bytePosEnd - $this->io->offset),
        );
    }

    private function parseScriptLimitsTag(int $bytePosEnd): ScriptLimitsTag
    {
        return new ScriptLimitsTag(
            maxRecursionDepth: $this->io->readUI16(),
            scriptTimeoutSeconds: $this->io->readUI16(),
        );
    }

    private function parseSetTabIndexTag(int $bytePosEnd): SetTabIndexTag
    {
        return new SetTabIndexTag(
            depth: $this->io->readUI16(),
            tabIndex: $this->io->readUI16(),
        );
    }

    private function parseFileAttributesTag(int $bytePosEnd): FileAttributesTag
    {
        $this->io->skipBits(1); // Reserved
        $useDirectBlit = $this->io->readBool();
        $useGPU = $this->io->readBool();
        $hasMetadata = $this->io->readBool();
        $actionScript3 = $this->io->readBool();
        $this->io->skipBits(2); // Reserved
        $useNetwork = $this->io->readBool();
        $this->io->skipBits(24); // Reserved

        return new FileAttributesTag(
            useDirectBlit: $useDirectBlit,
            useGpu: $useGPU,
            hasMetadata: $hasMetadata,
            actionScript3: $actionScript3,
            useNetwork: $useNetwork,
        );
    }

    private function parsePlaceObject3Tag(int $bytePosEnd): PlaceObject3Tag
    {
        $placeFlagHasClipActions = $this->io->readBool();
        $placeFlagHasClipDepth = $this->io->readBool();
        $placeFlagHasName = $this->io->readBool();
        $placeFlagHasRatio = $this->io->readBool();
        $placeFlagHasColorTransform = $this->io->readBool();
        $placeFlagHasMatrix = $this->io->readBool();
        $placeFlagHasCharacter = $this->io->readBool();
        $placeFlagMove = $this->io->readBool();

        $this->io->skipBits(3); // Reserved, must be 0
        $placeFlagHasImage = $this->io->readBool();
        $placeFlagHasClassName = $this->io->readBool();
        $placeFlagHasCacheAsBitmap = $this->io->readBool();
        $placeFlagHasBlendMode = $this->io->readBool();
        $placeFlagHasFilterList = $this->io->readBool();

        return new PlaceObject3Tag(
            move: $placeFlagMove,
            hasImage: $placeFlagHasImage,
            depth: $this->io->readUI16(),
            className: $placeFlagHasClassName || ($placeFlagHasImage && $placeFlagHasCharacter) ? $this->io->readNullTerminatedString() : null,
            characterId: $placeFlagHasCharacter ? $this->io->readUI16() : null,
            matrix: $placeFlagHasMatrix ? Matrix::read($this->io) : null,
            colorTransform: $placeFlagHasColorTransform ? ColorTransform::read($this->io, true) : null,
            ratio: $placeFlagHasRatio ? $this->io->readUI16() : null,
            name: $placeFlagHasName ? $this->io->readNullTerminatedString() : null,
            clipDepth: $placeFlagHasClipDepth ? $this->io->readUI16() : null,
            surfaceFilterList: $placeFlagHasFilterList ? $this->rec->collectFilterList() : null,
            blendMode: $placeFlagHasBlendMode ? $this->io->readUI8() : null,
            bitmapCache: $placeFlagHasCacheAsBitmap ? $this->io->readUI8() : null,
            clipActions: $placeFlagHasClipActions ? $this->rec->collectClipActions($this->swfVersion) : null,
        );
    }

    private function parseDefineFontAlignZonesTag(int $bytePosEnd): DefineFontAlignZonesTag
    {
        $fontId = $this->io->readUI16();
        $csmTableHint = $this->io->readUB(2);
        $this->io->skipBits(6); // Reserved
        $zoneTable = $this->rec->collectZoneTable($bytePosEnd);

        return new DefineFontAlignZonesTag(
            fontId: $fontId,
            csmTableHint: $csmTableHint,
            zoneTable: $zoneTable,
        );
    }

    private function parseCSMTextSettingsTag(int $bytePosEnd): CSMTextSettingsTag
    {
        $textId = $this->io->readUI16();
        $useFlashType = $this->io->readUB(2);
        $gridFit = $this->io->readUB(3);
        $this->io->skipBits(3); // Reserved
        $thickness = $this->io->readFixed(); //XXX F32 in the spec
        $sharpness = $this->io->readFixed(); //XXX F32 in the spec
        $this->io->skipBytes(1); // Reserved

        return new CSMTextSettingsTag(
            textId: $textId,
            useFlashType: $useFlashType,
            gridFit: $gridFit,
            thickness: $thickness,
            sharpness: $sharpness,
        );
    }

    private function parseSymbolClassTag(int $bytePosEnd): SymbolClassTag
    {
        $numSymbols = $this->io->readUI16();
        $tags = [];
        $names = [];

        for ($i = 0; $i < $numSymbols; $i++) {
            $tags[] = $this->io->readUI16();
            $names[] = $this->io->readNullTerminatedString();
        }

        return new SymbolClassTag(
            tags: $tags,
            names: $names,
        );
    }

    private function parseMetadataTag(int $bytePosEnd): MetadataTag
    {
        return new MetadataTag($this->io->readNullTerminatedString());
    }

    private function parseDefineScalingGridTag(int $bytePosEnd): DefineScalingGridTag
    {
        return new DefineScalingGridTag(
            characterId: $this->io->readUI16(),
            splitter: Rectangle::read($this->io),
        );
    }

    private function parseDoABCTag(int $bytePosEnd): DoABCTag
    {
        return new DoABCTag(
            flags: $this->io->readUI32(),
            name: $this->io->readNullTerminatedString(),
            data: $this->io->readBytes($bytePosEnd - $this->io->offset),
        );
    }

    private function parseDefineSceneAndFrameLabelDataTag(int $bytePosEnd): DefineSceneAndFrameLabelDataTag
    {
        $sceneOffsets = [];
        $sceneNames = [];
        $sceneCount = $this->io->readEncodedU32();
        for ($i = 0; $i < $sceneCount; $i++) {
            $sceneOffsets[] = $this->io->readEncodedU32();
            $sceneNames[] = $this->io->readNullTerminatedString();
        }

        $frameNumbers = [];
        $frameLabels = [];
        $frameLabelCount = $this->io->readEncodedU32();
        for ($i = 0; $i < $frameLabelCount; $i++) {
            $frameNumbers[] = $this->io->readEncodedU32();
            $frameLabels[] = $this->io->readNullTerminatedString();
        }

        return new DefineSceneAndFrameLabelDataTag(
            sceneOffsets: $sceneOffsets,
            sceneNames: $sceneNames,
            frameNumbers: $frameNumbers,
            frameLabels: $frameLabels,
        );
    }

    private function parseDefineBinaryDataTag(int $bytePosEnd): DefineBinaryDataTag
    {
        $tag = $this->io->readUI16();
        $this->io->skipBytes(4); // Reserved, must be 0
        $data = $this->io->readBytes($bytePosEnd - $this->io->offset);

        return new DefineBinaryDataTag($tag, $data);
    }

    private function parseDefineFontNameTag(int $bytePosEnd): DefineFontNameTag
    {
        return new DefineFontNameTag(
            fontId: $this->io->readUI16(),
            fontName: $this->io->readNullTerminatedString(),
            fontCopyright: $this->io->readNullTerminatedString(),
        );
    }

    private function parseDefineFont4Tag(int $bytePosEnd): DefineFont4Tag
    {
        $fontId = $this->io->readUI16();

        $this->io->skipBits(5); // Reserved
        $fontFlagsHasFontData = $this->io->readBool();
        $fontFlagsItalic = $this->io->readBool();
        $fontFlagsBold = $this->io->readBool();

        $fontName = $this->io->readNullTerminatedString();

        $fontData = $fontFlagsHasFontData ? $this->io->readBytes($bytePosEnd - $this->io->offset) : null;

        return new DefineFont4Tag(
            fontId: $fontId,
            italic: $fontFlagsItalic,
            bold: $fontFlagsBold,
            name: $fontName,
            data: $fontData,
        );
    }

    private function parseProductInfoTag(int $bytePosEnd): ProductInfo
    {
        return new ProductInfo(
            productId: $this->io->readUI32(),
            edition: $this->io->readUI32(),
            majorVersion: $this->io->readUI8(),
            minorVersion: $this->io->readUI8(),
            buildNumber: $this->io->readSI64(),
            compilationDate: $this->io->readSI64(),
        );
    }
}
