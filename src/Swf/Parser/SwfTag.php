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
use Exception;

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
        private SwfIO $io,
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

        while ($io->bytePos < $len) {
            // Collect record header (short or long)
            $recordHeader = $io->collectUI16();
            $tagType = $recordHeader >> 6;
            $tagLength = $recordHeader & 0x3f;

            if ($tagLength === 0x3f) {
                $tagLength = $io->collectSI32();
            }

            // For definition tags, collect the 'id' also
            if ($this->isDefinitionTagType($tagType)) {
                $tags[] = new SwfTagPosition(
                    type: $tagType,
                    offset: $io->bytePos,
                    length: $tagLength,
                    id: $io->collectUI16(),
                );
                $io->bytePos += $tagLength - 2; // 2 bytes already consumed
            } else {
                $tags[] = new SwfTagPosition(
                    type: $tagType,
                    offset: $io->bytePos,
                    length: $tagLength,
                );
                $io->bytePos += $tagLength;
            }
        }

        return $tags;
    }

    private function isDefinitionTagType(int $tagType): bool
    {
        switch ($tagType) {
            case  2: // DefineShape
            case 22: // DefineShape2
            case 32: // DefineShape3
            case 83: // DefineShape4
                return true; // shapeId
            case 10: // DefineFont
            case 48: // DefineFont2
            case 75: // DefineFont3
            case 91: // DefineFont4
                return true; // fontId
            case  7: // DefineButton
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
            case  6: // DefineBits
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

        $this->io->bytePos = $tagOffset;

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
            777 => new ReflexTag($this->io->collectBytes($bytePosEnd - $this->io->bytePos)),
            default => new UnknownTag(
                code: $tagType,
                data: $this->io->collectBytes($bytePosEnd - $this->io->bytePos),
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

        if ($this->io->bytePos > $bytePosEnd) {
            $this->errorCollector?->add(
                $tag,
                TagParseErrorType::ReadAfterEnd,
                [
                    'length' => $this->io->bytePos - $bytePosEnd,
                    'data' => substr($this->io->b, $bytePosEnd, $this->io->bytePos - $bytePosEnd),
                ]
            );
        } elseif ($this->io->bytePos < $bytePosEnd) {
            $this->errorCollector?->add(
                $tag,
                TagParseErrorType::ExtraBytes,
                [
                    'length' => $bytePosEnd - $this->io->bytePos,
                    'data' => substr($this->io->b, $this->io->bytePos, $bytePosEnd - $this->io->bytePos),
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
                shapeId: $this->io->collectUI16(),
                shapeBounds: $this->rec->collectRect(),
                shapes: $this->rec->collectShapeWithStyle($shapeVersion),
            );
        }

        return new DefineShape4Tag(
            shapeId: $this->io->collectUI16(),
            shapeBounds: $this->rec->collectRect(),
            edgeBounds: $this->rec->collectRect(),
            reserved: $this->io->collectUB(5),
            usesFillWindingRule: $this->io->collectUB(1) === 1,
            usesNonScalingStrokes: $this->io->collectUB(1) === 1,
            usesScalingStrokes: $this->io->collectUB(1) === 1,
            shapes: $this->rec->collectShapeWithStyle($shapeVersion),
        );
    }

    private function parsePlaceObjectTag(int $bytePosEnd): PlaceObjectTag
    {
        return new PlaceObjectTag(
            characterId: $this->io->collectUI16(),
            depth: $this->io->collectUI16(),
            matrix: $this->rec->collectMatrix(),
            colorTransform: $this->io->bytePos < $bytePosEnd ? $this->rec->collectColorTransform(false) : null,
        );
    }

    private function parseRemoveObjectTag(int $bytePosEnd, int $version): RemoveObjectTag|RemoveObject2Tag
    {
        return match ($version) {
            1 => new RemoveObjectTag(
                characterId: $this->io->collectUI16(),
                depth: $this->io->collectUI16(),
            ),
            2 => new RemoveObject2Tag(
                depth: $this->io->collectUI16(),
            ),
        };
    }

    private function parseDefineBitsTag(int $bytePosEnd): DefineBitsTag
    {
        return new DefineBitsTag(
            characterId: $this->io->collectUI16(),
            imageData: $this->io->collectBytes($bytePosEnd - $this->io->bytePos),
        );
    }

    private function parseDefineButtonTag(int $bytePosEnd, int $version): DefineButtonTag|DefineButton2Tag
    {
        if ($version === 1) {
            return new DefineButtonTag(
                buttonId: $this->io->collectUI16(),
                characters: $this->rec->collectButtonRecords($version),
                actions: $this->rec->collectActionRecords($bytePosEnd),
            );
        }

        $buttonId = $this->io->collectUI16();
        $this->io->collectUB(7); // Reserved, must be 0
        $taskAsMenu = $this->io->collectUB(1) === 1;
        $actionOffset = $this->io->collectUI16();
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
        return new JPEGTablesTag($this->io->collectBytes($bytePosEnd - $this->io->bytePos));
    }

    private function parseSetBackgroundColorTag(int $bytePosEnd): SetBackgroundColorTag
    {
        return new SetBackgroundColorTag(
            red: $this->io->collectUI8(),
            green: $this->io->collectUI8(),
            blue: $this->io->collectUI8(),
        );
    }

    private function parseDefineFontTag(int $bytePosEnd): DefineFontTag
    {
        $fontId = $this->io->collectUI16();
        // Collect and push back 1st element of OffsetTable (this is numGlyphs * 2)
        $numGlyphs = $this->io->collectUI16() / 2;
        $this->io->bytePos -= 2;
        $offsetTable = array();
        for ($i = 0; $i < $numGlyphs; $i++) {
            $offsetTable[] = $this->io->collectUI16();
        }
        $glyphShapeData = array();
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
            characterId: $this->io->collectUI16(),
            textBounds: $this->rec->collectRect(),
            textMatrix: $this->rec->collectMatrix(),
            glyphBits: $glyphBits = $this->io->collectUI8(),
            advanceBits: $advanceBits = $this->io->collectUI8(),
            textRecords: $this->rec->collectTextRecords($glyphBits, $advanceBits, $textVersion),
        );
    }

    private function parseDoActionTag(int $bytePosEnd): DoActionTag
    {
        return new DoActionTag($this->rec->collectActionRecords($bytePosEnd));
    }

    private function parseDefineFontInfoTag(int $bytePosEnd, int $version): DefineFontInfoTag
    {
        $fontId = $this->io->collectUI16();
        $fontNameLen = $this->io->collectUI8();
        $fontName = $this->io->collectBytes($fontNameLen);

        $this->io->collectUB(2); // Reserved
        $fontFlagsSmallText = $this->io->collectUB(1) === 1;
        $fontFlagsShiftJIS = $this->io->collectUB(1) === 1;
        $fontFlagsANSI = $this->io->collectUB(1) === 1;
        $fontFlagsItalic = $this->io->collectUB(1) === 1;
        $fontFlagsBold = $this->io->collectUB(1) === 1;
        $fontFlagsWideCodes = $this->io->collectUB(1) === 1;
        $languageCode = null;
        $codeTable = [];

        if ($version === 1) {
            while ($this->io->bytePos < $bytePosEnd) {
                $codeTable[] = $fontFlagsWideCodes ? $this->io->collectUI16() : $this->io->collectUI8();
            }
        } elseif ($version === 2) {
            $languageCode = $this->io->collectUI8();

            while ($this->io->bytePos < $bytePosEnd) {
                $codeTable[] = $this->io->collectUI16();
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
            soundId: $this->io->collectUI16(),
            soundFormat: $this->io->collectUB(4),
            soundRate: $this->io->collectUB(2),
            soundSize: $this->io->collectUB(1),
            soundType: $this->io->collectUB(1),
            soundSampleCount: $this->io->collectUI32(),
            soundData: $this->io->collectBytes($bytePosEnd - $this->io->bytePos),
        );
    }

    private function parseStartSoundTag(int $bytePosEnd, int $version): StartSoundTag|StartSound2Tag
    {
        return match ($version) {
            1 => new StartSoundTag(
                soundId: $this->io->collectUI16(),
                soundInfo: $this->rec->collectSoundInfo(),
            ),
            2 => new StartSound2Tag(
                soundClassName: $this->io->collectString(),
                soundInfo: $this->rec->collectSoundInfo(),
            ),
        };
    }

    private function parseDefineButtonSoundTag(int $bytePosEnd): DefineButtonSoundTag
    {
        return new DefineButtonSoundTag(
            buttonId: $this->io->collectUI16(),
            buttonSoundChar0: $char0 = $this->io->collectUI16(),
            buttonSoundInfo0: $char0 !== 0 ? $this->rec->collectSoundInfo() : null,
            buttonSoundChar1: $char1 = $this->io->collectUI16(),
            buttonSoundInfo1: $char1 !== 0 ? $this->rec->collectSoundInfo() : null,
            buttonSoundChar2: $char2 = $this->io->collectUI16(),
            buttonSoundInfo2: $char2 !== 0 ? $this->rec->collectSoundInfo() : null,
            buttonSoundChar3: $char3 = $this->io->collectUI16(),
            buttonSoundInfo3: $char3 !== 0 ? $this->rec->collectSoundInfo() : null,
        );
    }

    private function parseSoundStreamHeadTag(int $bytePosEnd, int $version): SoundStreamHeadTag
    {
        $this->io->collectUB(4); // Reserved

        $playbackSoundRate = $this->io->collectUB(2);
        $playbackSoundSize = $this->io->collectUB(1);
        $playbackSoundType = $this->io->collectUB(1);

        $streamSoundCompression = $this->io->collectUB(4);
        $streamSoundRate = $this->io->collectUB(2);
        $streamSoundSize = $this->io->collectUB(1);
        $streamSoundType = $this->io->collectUB(1);
        $streamSoundSampleCount = $this->io->collectUI16();

        $latencySeek = $streamSoundSampleCount === 2 ? $this->io->collectSI16() : null; // MP3

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
        return new SoundStreamBlockTag($this->io->collectBytes($bytePosEnd - $this->io->bytePos));
    }

    private function parseDefineBitsLosslessTag(int $bytePosEnd, int $version): DefineBitsLosslessTag
    {
        $characterId = $this->io->collectUI16();
        $bitmapFormat = $this->io->collectUI8();
        $bitmapWidth = $this->io->collectUI16();
        $bitmapHeight = $this->io->collectUI16();

        if ($bitmapFormat == 3) {
            $colors = $this->io->collectUI8();
            $data = gzuncompress($this->io->collectBytes($bytePosEnd - $this->io->bytePos)); // ZLIB uncompress
            $colorTableSize = match ($version) {
                1 => 3 * ($colors + 1), // 3 bytes per RGB value
                2 => 4 * ($colors + 1), // 4 bytes per RGBA value
            };

            $colorTable = substr($data, 0, $colorTableSize);
            $pixelData = substr($data, $colorTableSize);
        } elseif ($bitmapFormat == 4 || $bitmapFormat == 5) {
            $colorTable = null;
            $pixelData = gzuncompress($this->io->collectBytes($bytePosEnd - $this->io->bytePos)); // ZLIB uncompress
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
                    characterId: $this->io->collectUI16(),
                    imageData: $this->io->collectBytes($bytePosEnd - $this->io->bytePos),
                );

            case 3:
                return new DefineBitsJPEG3Tag(
                    characterId: $this->io->collectUI16(),
                    imageData: $this->io->collectBytes($this->io->collectUI32()),
                    alphaData: gzuncompress($this->io->collectBytes($bytePosEnd - $this->io->bytePos)), // ZLIB uncompress alpha channel
                );

            case 4:
                $characterId = $this->io->collectUI16();
                $alphaDataOffset = $this->io->collectUI32();
                $deblockParam = $this->io->collectUI16();
                $imageData = $this->io->collectBytes($alphaDataOffset);
                $alphaData = gzuncompress($this->io->collectBytes($bytePosEnd - $this->io->bytePos)); // ZLIB uncompress alpha channel

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
            buttonId: $this->io->collectUI16(),
            colorTransform: $this->rec->collectColorTransform(false),
        );
    }

    private function parseProtectTag(int $bytePosEnd): ProtectTag
    {
        return new ProtectTag(
            // Password is only present if tag length is not 0
            // It's stored as a null-terminated string
            password: $bytePosEnd > $this->io->bytePos ? $this->io->collectString() : null,
        );
    }

    private function parsePlaceObject2Tag(int $bytePosEnd, int $swfVersion): PlaceObject2Tag
    {
        $placeFlagHasClipActions = $this->io->collectUB(1) === 1;
        $placeFlagHasClipDepth = $this->io->collectUB(1) === 1;
        $placeFlagHasName = $this->io->collectUB(1) === 1;
        $placeFlagHasRatio = $this->io->collectUB(1) === 1;
        $placeFlagHasColorTransform = $this->io->collectUB(1) === 1;
        $placeFlagHasMatrix = $this->io->collectUB(1) === 1;
        $placeFlagHasCharacter = $this->io->collectUB(1) === 1;
        $placeFlagMove = $this->io->collectUB(1) === 1;

        return new PlaceObject2Tag(
            placeFlagMove: $placeFlagMove,
            depth: $this->io->collectUI16(),
            characterId: $placeFlagHasCharacter ? $this->io->collectUI16() : null,
            matrix: $placeFlagHasMatrix ? $this->rec->collectMatrix() : null,
            colorTransform: $placeFlagHasColorTransform ? $this->rec->collectColorTransform(true) : null,
            ratio: $placeFlagHasRatio ? $this->io->collectUI16() : null,
            name: $placeFlagHasName ? $this->io->collectString() : null,
            clipDepth: $placeFlagHasClipDepth ? $this->io->collectUI16() : null,
            clipActions: $placeFlagHasClipActions ? $this->rec->collectClipActions($swfVersion) : null,
        );
    }

    private function parseDefineEditTextTag(int $bytePosEnd): DefineEditTextTag
    {
        $characterId = $this->io->collectUI16();
        $bounds = $this->rec->collectRect();

        $flags = $this->io->collectUI8();
        $hasText = ($flags & 0b10000000) === 0b10000000;
        $wordWrap = ($flags & 0b01000000) === 0b01000000;
        $multiline = ($flags & 0b00100000) === 0b00100000;
        $password = ($flags & 0b00010000) === 0b00010000;
        $readOnly = ($flags & 0b00001000) === 0b00001000;
        $hasTextColor = ($flags & 0b00000100) === 0b00000100;
        $hasMaxLength = ($flags & 0b00000010) === 0b00000010;
        $hasFont = ($flags & 0b00000001) === 0b00000001;

        $flags = $this->io->collectUI8();
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
            fontId: $hasFont ? $this->io->collectUI16() : null,
            fontClass: $hasFontClass ? $this->io->collectString() : null,
            fontHeight: $hasFont ? $this->io->collectUI16() : null,
            textColor: $hasTextColor ? $this->rec->collectRGBA() : null,
            maxLength: $hasMaxLength ? $this->io->collectUI16() : null,
            layout: $hasLayout ? [
                'align' => $this->io->collectUI8(),
                'leftMargin' => $this->io->collectUI16(),
                'rightMargin' => $this->io->collectUI16(),
                'indent' => $this->io->collectUI16(),
                'leading' => $this->io->collectSI16(),
            ] : null,
            variableName: $this->io->collectString(),
            initialText: $hasText ? $this->io->collectString() : null,
        );
    }

    private function parseDefineSpriteTag(int $bytePosEnd): DefineSpriteTag
    {
        $spriteId = $this->io->collectUI16();
        $frameCount = $this->io->collectUI16();
        $b = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);

        $io = new SwfIO($b);
        $rec = new SwfRec($io);
        $tag = new SwfTag($io, $rec, $this->swfVersion, $this->errorCollector);

        // Collect and parse tags
        $tags = [];

        while ($io->bytePos < strlen($io->b)) {
            $recordHeader = $io->collectUI16();
            $tagType = $recordHeader >> 6;
            $tagLength = $recordHeader & 0x3f;
            if ($tagLength == 0x3f) {
                $tagLength = $io->collectSI32();
            }
            $bytePosEnd = $io->bytePos + $tagLength;
            $tags[] = $tag->parseTag(new SwfTagPosition($tagType, $io->bytePos, $tagLength));
            $io->bytePos = $bytePosEnd;
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
        $label = $this->io->collectString();

        // Since SWF 6, the flag namedAnchor is present to create a named anchor
        // So we need to check if there is still data to read, and if so, read the flag
        $hasMoreData = $this->io->bytePos < $bytePosEnd;

        return new FrameLabelTag(
            $label,
            $hasMoreData && $this->io->collectUI8() === 1,
        );
    }

    private function parseDefineMorphShapeTag(int $bytePosEnd, int $version): DefineMorphShapeTag|DefineMorphShape2Tag
    {
        $characterId = $this->io->collectUI16();
        $startBounds = $this->rec->collectRect();
        $endBounds = $this->rec->collectRect();

        if ($version === 1) {
            return new DefineMorphShapeTag(
                characterId: $characterId,
                startBounds: $startBounds,
                endBounds: $endBounds,
                offset: $this->io->collectUI32(),
                fillStyles: $this->rec->collectMorphFillStyleArray(),
                lineStyles: $this->rec->collectMorphLineStyleArray(1),
                startEdges: $this->rec->collectShape(1),
                endEdges: $this->rec->collectShape(1),
            );
        }

        $startEdgeBounds = $this->rec->collectRect();
        $endEdgeBounds = $this->rec->collectRect();
        $this->io->collectUB(6); // Reserved
        $usesNonScalingStrokes = $this->io->collectUB(1);
        $usesScalingStrokes = $this->io->collectUB(1);

        return new DefineMorphShape2Tag(
            characterId: $characterId,
            startBounds: $startBounds,
            endBounds: $endBounds,
            startEdgeBounds: $startEdgeBounds,
            endEdgeBounds: $endEdgeBounds,
            usesNonScalingStrokes: $usesNonScalingStrokes,
            usesScalingStrokes: $usesScalingStrokes,
            offset: $this->io->collectUI32(),
            fillStyles: $this->rec->collectMorphFillStyleArray(),
            lineStyles: $this->rec->collectMorphLineStyleArray(2),
            startEdges: $this->rec->collectShape(1), // @todo version ?
            endEdges: $this->rec->collectShape(1), // @todo version ?
        );
    }

    private function parseDefineFont23Tag(int $bytePosEnd, int $version): DefineFont2Or3Tag
    {
        $fontId = $this->io->collectUI16();

        $flags = $this->io->collectUI8();
        $fontFlagsHasLayout = ($flags & 0b10000000) === 0b10000000;
        $fontFlagsShiftJIS = ($flags & 0b01000000) === 0b01000000;
        $fontFlagsSmallText = ($flags & 0b00100000) === 0b00100000;
        $fontFlagsANSI = ($flags & 0b00010000) === 0b00010000;
        $fontFlagsWideOffsets = ($flags & 0b00001000) === 0b00001000;
        $fontFlagsWideCodes = ($flags & 0b00000100) === 0b00000100;
        $fontFlagsItalic = ($flags & 0b00000010) === 0b00000010;
        $fontFlagsBold = ($flags & 0b00000001) === 0b00000001;

        $languageCode = $this->io->collectUI8();
        $fontNameLength = $this->io->collectUI8();
        $fontName = substr($this->io->collectBytes($fontNameLength), 0, -1); // Remove trailing NULL
        $numGlyphs = $this->io->collectUI16();

        $offsetTable = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            $offsetTable[] = $fontFlagsWideOffsets ? $this->io->collectUI32() : $this->io->collectUI16();
        }

        $codeTableOffset = $fontFlagsWideOffsets ? $this->io->collectUI32() : $this->io->collectUI16();

        $glyphShapeTable = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            $glyphShapeTable[] = $this->rec->collectShape(1);
        }

        $codeTable = [];
        for ($i = 0; $i < $numGlyphs; $i++) {
            if ($version === 2) {
                $codeTable[] = $fontFlagsWideCodes ? $this->io->collectUI16() : $this->io->collectUI8();
            } else if ($version === 3) {
                $codeTable[] = $this->io->collectUI16();
            }
        }

        if ($fontFlagsHasLayout) {
            $layout = [];
            $layout['fontAscent'] = $this->io->collectSI16();
            $layout['fontDescent'] = $this->io->collectSI16();
            $layout['fontLeading'] = $this->io->collectSI16();
            $layout['fontAdvanceTable'] = array();
            for ($i = 0; $i < $numGlyphs; $i++) {
                $layout['fontAdvanceTable'][] = $this->io->collectSI16();
            }
            $layout['fontBoundsTable'] = array();
            for ($i = 0; $i < $numGlyphs; $i++) {
                $layout['fontBoundsTable'][] = $this->rec->collectRect();
            }
            $kerningCount = $this->io->collectUI16();
            $layout['fontKerningTable'] = array();
            for ($i = 0; $i < $kerningCount; $i++) {
                $fontKerningCode1 = $fontFlagsWideCodes ? $this->io->collectUI16() : $this->io->collectUI8();
                $fontKerningCode2 = $fontFlagsWideCodes ? $this->io->collectUI16() : $this->io->collectUI8();
                $fontKerningAdjustment = $this->io->collectSI16();
                $layout['fontKerningTable'][] = [
                    'fontKerningCode1' => $fontKerningCode1,
                    'fontKerningCode2' => $fontKerningCode2,
                    'fontKerningAdjustment' => $fontKerningAdjustment
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
        $count = $this->io->collectUI16();

        for ($i = 0; $i < $count; $i++) {
            $tags[] = $this->io->collectUI16();
            $names[] = $this->io->collectString();
        }

        return new ExportAssetsTag(
            tags: $tags,
            names: $names,
        );
    }

    private function parseImportAssetsTag(int $bytePosEnd, int $version): ImportAssetsTag
    {
        $url = $this->io->collectString();

        if ($version === 2) {
            $this->io->collectUI8(); // Reserved, must be 1
            $this->io->collectUI8(); // Reserved, must be 0
        }

        $tags = [];
        $names = [];
        $count = $this->io->collectUI16();

        for ($i = 0; $i < $count; $i++) {
            $tags[] = $this->io->collectUI16();
            $names[] = $this->io->collectString();
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
            $this->io->collectUI16(); // Reserved, must be 0
        }

        return new EnableDebuggerTag(
            version: $version,
            password: $this->io->collectString(),
        );
    }

    private function parseDoInitActionTag(int $bytePosEnd): DoInitActionTag
    {
        return new DoInitActionTag(
            spriteId: $this->io->collectUI16(),
            actions: $this->rec->collectActionRecords($bytePosEnd),
        );
    }

    private function parseDefineVideoStreamTag(int $bytePosEnd): DefineVideoStreamTag
    {
        $characterId = $this->io->collectUI16();
        $numFrames = $this->io->collectUI16();
        $width = $this->io->collectUI16();
        $height = $this->io->collectUI16();

        $this->io->collectUB(4); // Reserved
        $videoFlagsDeblocking = $this->io->collectUB(3);
        $videoFlagsSmoothing = $this->io->collectUB(1);

        $codecId = $this->io->collectUI8();

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
            streamId: $this->io->collectUI16(),
            frameNum: $this->io->collectUI16(),
            videoData: $this->io->collectBytes($bytePosEnd - $this->io->bytePos),
        );
    }

    private function parseScriptLimitsTag(int $bytePosEnd): ScriptLimitsTag
    {
        return new ScriptLimitsTag(
            maxRecursionDepth: $this->io->collectUI16(),
            scriptTimeoutSeconds: $this->io->collectUI16(),
        );
    }

    private function parseSetTabIndexTag(int $bytePosEnd): SetTabIndexTag
    {
        return new SetTabIndexTag(
            depth: $this->io->collectUI16(),
            tabIndex: $this->io->collectUI16(),
        );
    }

    private function parseFileAttributesTag(int $bytePosEnd): FileAttributesTag
    {
        $this->io->collectUB(1); // Reserved
        $useDirectBlit = $this->io->collectUB(1) === 1;
        $useGPU = $this->io->collectUB(1) === 1;
        $hasMetadata = $this->io->collectUB(1) === 1;
        $actionScript3 = $this->io->collectUB(1) === 1;
        $this->io->collectUB(2); // Reserved
        $useNetwork = $this->io->collectUB(1) === 1;
        $this->io->collectUB(24); // Reserved

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
        $placeFlagHasClipActions = $this->io->collectUB(1) === 1;
        $placeFlagHasClipDepth = $this->io->collectUB(1) === 1;
        $placeFlagHasName = $this->io->collectUB(1) === 1;
        $placeFlagHasRatio = $this->io->collectUB(1) === 1;
        $placeFlagHasColorTransform = $this->io->collectUB(1) === 1;
        $placeFlagHasMatrix = $this->io->collectUB(1) === 1;
        $placeFlagHasCharacter = $this->io->collectUB(1) === 1;
        $placeFlagMove = $this->io->collectUB(1) === 1;

        $this->io->collectUB(3); // Reserved, must be 0
        $placeFlagHasImage = $this->io->collectUB(1) === 1;
        $placeFlagHasClassName = $this->io->collectUB(1) === 1;
        $placeFlagHasCacheAsBitmap = $this->io->collectUB(1) === 1;
        $placeFlagHasBlendMode = $this->io->collectUB(1) === 1;
        $placeFlagHasFilterList = $this->io->collectUB(1) === 1;

        return new PlaceObject3Tag(
            move: $placeFlagMove,
            hasImage: $placeFlagHasImage,
            depth: $this->io->collectUI16(),
            className: $placeFlagHasClassName || ($placeFlagHasImage && $placeFlagHasCharacter) ? $this->io->collectString() : null,
            characterId: $placeFlagHasCharacter ? $this->io->collectUI16() : null,
            matrix: $placeFlagHasMatrix ? $this->rec->collectMatrix() : null,
            colorTransform: $placeFlagHasColorTransform ? $this->rec->collectColorTransform(true) : null,
            ratio: $placeFlagHasRatio ? $this->io->collectUI16() : null,
            name: $placeFlagHasName ? $this->io->collectString() : null,
            clipDepth: $placeFlagHasClipDepth ? $this->io->collectUI16() : null,
            surfaceFilterList: $placeFlagHasFilterList ? $this->rec->collectFilterList() : null,
            blendMode: $placeFlagHasBlendMode ? $this->io->collectUI8() : null,
            bitmapCache: $placeFlagHasCacheAsBitmap ? $this->io->collectUI8() : null,
            clipActions: $placeFlagHasClipActions ? $this->rec->collectClipActions($this->swfVersion) : null,
        );
    }

    private function parseDefineFontAlignZonesTag(int $bytePosEnd): DefineFontAlignZonesTag
    {
        $fontId = $this->io->collectUI16();
        $csmTableHint = $this->io->collectUB(2);
        $this->io->collectUB(6); // Reserved
        $zoneTable = $this->rec->collectZoneTable($bytePosEnd);

        return new DefineFontAlignZonesTag(
            fontId: $fontId,
            csmTableHint: $csmTableHint,
            zoneTable: $zoneTable,
        );
    }

    private function parseCSMTextSettingsTag(int $bytePosEnd): CSMTextSettingsTag
    {
        $textId = $this->io->collectUI16();
        $useFlashType = $this->io->collectUB(2);
        $gridFit = $this->io->collectUB(3);
        $this->io->collectUB(3); // Reserved
        $thickness = $this->io->collectFixed(); //XXX F32 in the spec
        $sharpness = $this->io->collectFixed(); //XXX F32 in the spec
        $this->io->collectUI8(); // Reserved

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
        $numSymbols = $this->io->collectUI16();
        $tags = [];
        $names = [];

        for ($i = 0; $i < $numSymbols; $i++) {
            $tags[] = $this->io->collectUI16();
            $names[] = $this->io->collectString();
        }

        return new SymbolClassTag(
            tags: $tags,
            names: $names,
        );
    }

    private function parseMetadataTag(int $bytePosEnd): MetadataTag
    {
        return new MetadataTag($this->io->collectString());
    }

    private function parseDefineScalingGridTag(int $bytePosEnd): DefineScalingGridTag
    {
        return new DefineScalingGridTag(
            characterId: $this->io->collectUI16(),
            splitter: $this->rec->collectRect(),
        );
    }

    private function parseDoABCTag(int $bytePosEnd): DoABCTag
    {
        return new DoABCTag(
            flags: $this->io->collectUI32(),
            name: $this->io->collectString(),
            data: $this->io->collectBytes($bytePosEnd - $this->io->bytePos),
        );
    }

    private function parseDefineSceneAndFrameLabelDataTag(int $bytePosEnd): DefineSceneAndFrameLabelDataTag
    {
        $sceneOffsets = [];
        $sceneNames = [];
        $sceneCount = $this->io->collectEncodedU32();
        for ($i = 0; $i < $sceneCount; $i++) {
            $sceneOffsets[] = $this->io->collectEncodedU32();
            $sceneNames[] = $this->io->collectString();
        }

        $frameNumbers = [];
        $frameLabels = [];
        $frameLabelCount = $this->io->collectEncodedU32();
        for ($i = 0; $i < $frameLabelCount; $i++) {
            $frameNumbers[] = $this->io->collectEncodedU32();
            $frameLabels[] = $this->io->collectString();
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
        $tag = $this->io->collectUI16();
        $this->io->collectUI32(); // Reserved, must be 0
        $data = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);

        return new DefineBinaryDataTag($tag, $data);
    }

    private function parseDefineFontNameTag(int $bytePosEnd): DefineFontNameTag
    {
        return new DefineFontNameTag(
            fontId: $this->io->collectUI16(),
            fontName: $this->io->collectString(),
            fontCopyright: $this->io->collectString(),
        );
    }

    private function parseDefineFont4Tag(int $bytePosEnd): DefineFont4Tag
    {
        $fontId = $this->io->collectUI16();

        $this->io->collectUB(5); // Reserved
        $fontFlagsHasFontData = $this->io->collectUB(1) === 1;
        $fontFlagsItalic = $this->io->collectUB(1) === 1;
        $fontFlagsBold = $this->io->collectUB(1) === 1;

        $fontName = $this->io->collectString();

        $fontData = $fontFlagsHasFontData ? $this->io->collectBytes($bytePosEnd - $this->io->bytePos) : null;

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
            productId: $this->io->collectUI32(),
            edition: $this->io->collectUI32(),
            majorVersion: $this->io->collectUI8(),
            minorVersion: $this->io->collectUI8(),
            buildNumber: $this->io->collectSI64(),
            compilationDate: $this->io->collectSI64(),
        );
    }
}
