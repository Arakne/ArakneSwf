<?php

/*
    SWF.php: Macromedia Flash (SWF) file parser
    Copyright (C) 2012 Thanos Efraimidis (4real.gr)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class SWF {
    public $header; // Header
    public $tags; // Tags

    private $io; // SWFio for basic I/O
    private $rec; // SWFrec for simple and complex records
    private $hdr; // SWFhdr for header
    private $tag; // SWFtag for tags

    public function __construct($b) {
        $this->io = new SWFio($b);
        $this->rec = new SWFrec($this->io);
        $this->hdr = new SWFhdr($this->io, $this->rec);
        $this->header = $this->hdr->parseHeader();
        $this->tag = new SWFtag($this->io, $this->rec, $this->header['version']);
        $this->tags = $this->tag->parseTags();
    }

    public function parseTag($tag) {
        return $this->tag->parseTag($tag);
    }
}

class SWFhdr {
    private $io; // SWFio for basic I/O
    private $rec; // SWFrec for simple and complex records

    public function __construct($io, $rec) {
        $this->io = $io;
        $this->rec = $rec;
    }

    public function parseHeader() {
        $signature = $this->io->collectBytes(3);
        if ($signature == 'CWS') {
            $compressed = true;
        } else if ($signature == 'FWS') {
            $compressed = false;
        } else {
            throw new Exception(sprintf('Internal error: signature=[%s]', $signature));
        }
        $version = $this->io->collectUI8();
        $fileLength = $this->io->collectUI32();
        if ($compressed) {
            $this->io->doUncompress();
        }
        $frameSize = $this->rec->collectRect();
        $frameRate = $this->io->collectFixed8();
        $frameCount = $this->io->collectUI16();
        return
            array('signature' => $signature,
                'version' => $version,
                'fileLength' => $fileLength,
                'frameSize' => $frameSize,
                'frameRate' => $frameRate,
                'frameCount' => $frameCount);
    }
}

class SWFtag {
    private $io; // SWFio for basic I/O
    private $rec; // SWFrec for simple and complex records
    private $swfVersion; // Version of this SWF file
    private $bitPos;

    public function __construct($io, $rec, $swfVersion) {
        $this->io = $io;
        $this->rec = $rec;
        $this->swfVersion = $swfVersion;
    }

    public function parseTags() {
        $tags = array();
        while ($this->io->bytePos < strlen($this->io->b)) {
            // Collect record header (short or long)
            $recordHeader = $this->io->collectUI16();
            $tagType = $recordHeader >> 6;
            $tagLength = $recordHeader & 0x3f;
            if ($tagLength == 0x3f) {
                $tagLength = $this->io->collectSI32();
            }
            // For definition tags, collect the 'id' also
            if ($this->isDefinitionTagType($tagType)) {
                $tags[] = array
                ('type' => $tagType,
                    'offset' => $this->io->bytePos,
                    'length' => $tagLength,
                    'id' => $this->io->collectUI16());
                $this->io->bytePos += $tagLength - 2; // 2 bytes already consumed
            } else {
                $tags[] = array
                ('type' => $tagType,
                    'offset' => $this->io->bytePos,
                    'length' => $tagLength);
                $this->io->bytePos += $tagLength;
            }
        }
        return $tags;
    }

    private function isDefinitionTagType($tagType) {
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

    public function parseTag($tag) {
        $tagType = $tag['type'];
        $tagOffset = $tag['offset'];
        $tagLength = $tag['length'];

        $this->io->bytePos = $tagOffset;
        $this->bitPos = 0;

        $bytePosEnd = $tagOffset + $tagLength;

        switch ($tagType) {
            case 0: // End
                $ret = $this->parseEndTag($bytePosEnd);
                break;
            case 1: // ShowFrame
                $ret = $this->parseShowFrameTag($bytePosEnd);
                break;
            case 2: // DefineShape
                $ret = $this->parseDefineShapeTag($bytePosEnd, 1);
                break;
            case 4: // PlaceObject
                $ret = $this->parsePlaceObjectTag($bytePosEnd);
                break;
            case 5: // RemoveObject
                $ret = $this->parseRemoveObjectTag($bytePosEnd, 1);
                break;
            case 6: // DefineBits
                $ret = $this->parseDefineBitsTag($bytePosEnd);
                break;
            case 7: // DefineButton
                $ret = $this->parseDefineButtonTag($bytePosEnd, 1);
                break;
            case 8: // JPEGTables
                $ret = $this->parseJPEGTablesTag($bytePosEnd);
                break;
            case 9: // SetBackgroundColor
                $ret = $this->parseSetBackgroundColorTag($bytePosEnd);
                break;
            case 10: // DefineFont
                $ret = $this->parseDefineFontTag($bytePosEnd);
                break;
            case 11: // DefineText
                $ret = $this->parseDefineTextTag($bytePosEnd, 1);
                break;
            case 12: // DoAction
                $ret = $this->parseDoActionTag($bytePosEnd);
                break;
            case 13: // DefineFontInfo
                $ret = $this->parseDefineFontInfoTag($bytePosEnd, 1);
                break;
            case 14: // DefineSound
                $ret = $this->parseDefineSoundTag($bytePosEnd);
                break;
            case 15: // StartSound
                $ret = $this->parseStartSoundTag($bytePosEnd, 1);
                break;
            case 17: // DefineButtonSound
                $ret = $this->parseDefineButtonSoundTag($bytePosEnd);
                break;
            case 18: // SoundStreamHead
                $ret = $this->parseSoundStreamHeadTag($bytePosEnd, 1);
                break;
            case 19: // SoundStreamBlock
                $ret = $this->parseSoundStreamBlockTag($bytePosEnd);
                break;
            case 20: // DefineBitsLossless
                $ret = $this->parseDefineBitsLosslessTag($bytePosEnd, 1);
                break;
            case 21: // DefineBitsJPEG2
                $ret = $this->parseDefineBitsJPEGTag($bytePosEnd, 2);
                break;
            case 22: // DefineShape2
                $ret = $this->parseDefineShapeTag($bytePosEnd, 2);
                break;
            case 23: // DefineButtonCxform
                $ret = $this->parseDefineButtonCxformTag($bytePosEnd);
                break;
            case 24: // Protect
                $ret = $this->parseProtectTag($bytePosEnd);
                break;
            case 26: // PlaceObject2
                $ret = $this->parsePlaceObject2Tag($bytePosEnd, $this->swfVersion);
                break;
            case 28: // RemoveObject2
                $ret = $this->parseRemoveObjectTag($bytePosEnd, 2);
                break;
            case 32: // DefineShape3
                $ret = $this->parseDefineShapeTag($bytePosEnd, 3);
                break;
            case 33: // DefineText2
                $ret = $this->parseDefineTextTag($bytePosEnd, 2);
                break;
            case 34: // DefineButton2
                $ret = $this->parseDefineButtonTag($bytePosEnd, 2);
                break;
            case 35: // DefineBitsJPEG3
                $ret = $this->parseDefineBitsJPEGTag($bytePosEnd, 3);
                break;
            case 36: // DefineBitsLossless2
                $ret = $this->parseDefineBitsLosslessTag($bytePosEnd, 2);
                break;
            case 37: // DefineEditText
                $ret = $this->parseDefineEditTextTag($bytePosEnd);
                break;
            case 39: // DefineSprite
                $ret = $this->parseDefineSpriteTag($bytePosEnd);
                break;
            case 43: // FrameLabel
                $ret = $this->parseFrameLabelTag($bytePosEnd);
                break;
            case 45: // SoundStreamHead2
                $ret = $this->parseSoundStreamHeadTag($bytePosEnd, 2);
                break;
            case 46: // DefineMorphShape
                $ret = $this->parseDefineMorphShapeTag($bytePosEnd, 1);
                break;
            case 48: // DefineFont2
                $ret = $this->parseDefineFont23Tag($bytePosEnd, 2);
                break;
            case 56: // ExportAssets
                $ret = $this->parseExportAssetsTag($bytePosEnd);
                break;
            case 57: // ImportAssets
                $ret = $this->parseImportAssetsTag($bytePosEnd, 1);
                break;
            case 58: // EnableDebugger
                $ret = $this->parseEnableDebuggerTag($bytePosEnd, 1);
                break;
            case 59: // DoInitAction
                $ret = $this->parseDoInitActionTag($bytePosEnd);
                break;
            case 60: // DefineVideoStream
                $ret = $this->parseDefineVideoStreamTag($bytePosEnd);
                break;
            case 61: // VideoFrame
                $ret = $this->parseVideoFrameTag($bytePosEnd);
                break;
            case 62: // DefineFontInfo2
                $ret = $this->parseDefineFontInfoTag($bytePosEnd, 2);
                break;
            case 64: // EnableDebugger2
                $ret = $this->parseEnableDebuggerTag($bytePosEnd, 2);
                break;
            case 65: // ScriptLimits
                $ret = $this->parseScriptLimitsTag($bytePosEnd);
                break;
            case 66: // SetTabIndex
                $ret = $this->parseSetTabIndexTag($bytePosEnd);
                break;
            case 69: // FileAttributes
                $ret = $this->parseFileAttributesTag($bytePosEnd);
                break;
            case 70: // PlaceObject3
                $ret = $this->parsePlaceObject3Tag($bytePosEnd);
                break;
            case 71: // ImportAssets2
                $ret = $this->parseImportAssetsTag($bytePosEnd, 2);
                break;
            case 73: // DefineFontAlignZones
                $ret = $this->parseDefineFontAlignZonesTag($bytePosEnd);
                break;
            case 74: // CSMTextSettings
                $ret = $this->parseCSMTextSettingsTag($bytePosEnd);
                break;
            case 75: // DefineFont3
                $ret = $this->parseDefineFont23Tag($bytePosEnd, 3);
                break;
            case 76: // SymbolClass
                $ret = $this->parseSymbolClassTag($bytePosEnd);
                break;
            case 77: // Metadata
                $ret = $this->parseMetadataTag($bytePosEnd);
                break;
            case 78: // DefineScalingGrid
                $ret = $this->parseDefineScalingGridTag($bytePosEnd);
                break;
            case 82: // DoABC
                $ret = $this->parseDoAbcTag($bytePosEnd);
                break;
            case 83: // DefineShape4
                $ret = $this->parseDefineShapeTag($bytePosEnd, 4);
                break;
            case 84: // DefineMorphShape2
                $ret = $this->parseDefineMorphShapeTag($bytePosEnd, 2);
                break;
            case 86: // DefineSceneAndFrameLabelData
                $ret = $this->parseDefineSceneAndFrameLabelDataTag($bytePosEnd);
                break;
            case 87: // DefineBinaryData
                $ret = $this->parseDefineBinaryDataTag($bytePosEnd);
                break;
            case 88: // DefineFontName
                $ret = $this->parseDefineFontNameTag($bytePosEnd);
                break;
            case 89: // StartSound2
                $ret = $this->parseStartSoundTag($bytePosEnd, 2);
                break;
            case 90: // DefineBitsJPEG4
                $ret = $this->parseDefineBitsJPEGTag($bytePosEnd, 4);
                break;
            case 91: // DefineFont4
                $ret = $this->parseDefineFont4Tag($bytePosEnd);
                break;
            case 41:
            case 253:
            case 255: // Undocumented
                $ret = array();
                $ret['bytes'] = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);
                break;
            default:
                throw new Exception(sprintf('Internal error: tagType=%d', $tagType));
        }
        if ($this->io->bytePos != $tagOffset + $tagLength) {
            // Make sure we have consumed whole tag
            $sb = '';
            $sb .= sprintf("Internal error: tagType=%d, %d bytes left:",
                $tagType, $tagOffset + $tagLength - $this->io->bytePos);
            for ($o = $this->io->bytePos; $o < $tagOffset + $tagLength; $o++) {
                $sb .= sprintf(" %02x", ord($this->io->b[$o]));
            }
            error_log($sb);
        }
        return $ret;
    }

    private function parseEndTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'EndTag';
        return $ret;
    }

    private function parseShowFrameTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'ShowFrame';
        return $ret;
    }

    private function parseDefineShapeTag($bytePosEnd, $shapeVersion) {
        $ret = array();
        if ($shapeVersion == 1) {
            $ret['tagType'] = 'DefineShape';
        } else if ($shapeVersion == 2) {
            $ret['tagType'] = 'DefineShape2';
        } else if ($shapeVersion == 3) {
            $ret['tagType'] = 'DefineShape3';
        } else if ($shapeVersion == 4) {
            $ret['tagType'] = 'DefineShape4';
        }
        if ($shapeVersion == 1 || $shapeVersion == 2 || $shapeVersion == 3) {
            $ret['shapeId'] = $this->io->collectUI16();
            $ret['shapeBounds'] = $this->rec->collectRect();
            $ret['shapes'] = $this->rec->collectShapeWithStyle($shapeVersion);
        } else if ($shapeVersion == 4) {
            $ret['shapeId'] = $this->io->collectUI16();
            $ret['shapeBounds'] = $this->rec->collectRect();
            $ret['edgeBounds'] = $this->rec->collectRect();
            $this->io->collectUB(5); // Reserved, must be 0
            $ret['usesFillWindingRule'] = $this->io->collectUB(1);
            $ret['usesNonScalingStrokes'] = $this->io->collectUB(1);
            $ret['usesScalingStrokes'] = $this->io->collectUB(1);
            $ret['shapes'] = $this->rec->collectShapeWithStyle($shapeVersion);
        }
        return $ret;
    }

    private function parsePlaceObjectTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'PlaceObject';
        $ret['characterId'] = $this->io->collectUI16();
        $ret['depth'] = $this->io->collectUI16();
        $ret['matrix'] = $this->rec->collectMatrix();
        if ($this->io->bytePos < $bytePosEnd) {
            $ret['colorTransform'] = $this->rec->collectColorTransform(false);
        }
        return $ret;
    }

    private function parseRemoveObjectTag($bytePosEnd, $version) {
        $ret = array();
        if ($version == 1) {
            $ret['tagType'] = 'RemoveObject';
            $ret['characterId'] = $this->io->collectUI16();
            $ret['depth'] = $this->io->collectUI16();
        } else if ($version == 2) {
            $ret['tagType'] = 'RemoveObject2';
            $ret['depth'] = $this->io->collectUI16();
        }
        return $ret;
    }

    private function parseDefineBitsTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineBits';
        $ret['characterId'] = $this->io->collectUI16();
        $ret['imageData'] = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);
        return $ret;
    }

    private function parseDefineButtonTag($bytePosEnd, $version) {
        $ret = array();
        if ($version == 1) {
            $ret['tagType'] = 'DefineButton';
            $ret['buttonId'] = $this->io->collectUI16();
            $ret['characters'] = $this->rec->collectButtonRecords($version);
            $ret['actions'] = $this->rec->collectActionRecords($bytePosEnd);
        } else if ($version == 2) {
            $ret['tagType'] = 'DefineButton2';
            $ret['buttonId'] = $this->io->collectUI16();
            $this->io->collectUB(7); // Reserved, must be 0
            $ret['trackAsMenu'] = $this->io->collectUB(1);
            $here = $this->io->bytePos;
            $ret['actionOffset'] = $this->io->collectUI16();
            $ret['characters'] = $this->rec->collectButtonRecords($version);
            if ($ret['actionOffset'] != 0) {
                $ret['actions'] = $this->rec->collectButtonCondActions($bytePosEnd);
            }
        }
        return $ret;
    }

    private function parseJPEGTablesTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'JPEGTables';
        $ret['JPEGData'] = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);
        return $ret;
    }

    private function parseSetBackgroundColorTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'SetBackgroundColor';
        $ret['backgroundColor'] = $this->rec->collectRGB();
        return $ret;
    }

    private function parseDefineFontTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineFont';
        $ret['fontId'] = $this->io->collectUI16();
        // Collect and push back 1st element of OffsetTable (this is numGlyphs * 2)
        $numGlyphs = $this->io->collectUI16() / 2;
        $this->io->bytePos -= 2;
        $ret['offsetTable'] = array();
        for ($i = 0; $i < $numGlyphs; $i++) {
            $ret['offsetTable'][] = $this->io->collectUI16();
        }
        $ret['glyphShapeData'] = array();
        for ($i = 0; $i < $numGlyphs; $i++) {
            $ret['glyphShapeData'][] = $this->rec->collectShape(1);
        }
        return $ret;
    }

    private function parseDefineTextTag($bytePosEnd, $textVersion) {
        $ret = array();
        $ret['tagType'] = $textVersion == 1 ? 'DefineText' : 'DefineText2';
        $ret['characterId'] = $this->io->collectUI16();
        $ret['textBounds'] = $this->rec->collectRect();
        $ret['textMatrix'] = $this->rec->collectMatrix();
        $ret['glyphBits'] = $this->io->collectUI8();
        $ret['advanceBits'] = $this->io->collectUI8();
        $ret['textRecords'] = $this->rec->collectTextRecords($ret['glyphBits'], $ret['advanceBits'], $textVersion);
        return $ret;
    }

    private function parseDoActionTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DoAction';
        $ret['actions'] = $this->rec->collectActionRecords($bytePosEnd);
        return $ret;
    }

    private function parseDefineFontInfoTag($bytePosEnd, $version) {
        $ret = array();
        $ret['tagType'] = $version == 1 ? 'DefineFontInfo' : 'DefineFontInfo2';
        $ret['fontId'] = $this->io->collectUI16();
        $fontNameLen = $this->io->collectUI8();
        $ret['fontName'] = $this->io->collectBytes($fontNameLen);

        $this->io->collectUB(2); // Reserved
        $ret['fontFlagsSmallText'] = $this->io->collectUB(1);
        $ret['fontFlagsShiftJIS'] = $this->io->collectUB(1);
        $ret['fontFlagsANSI'] = $this->io->collectUB(1);
        $ret['fontFlagsItalic'] = $this->io->collectUB(1);
        $ret['fontFlagsBold'] = $this->io->collectUB(1);
        $ret['fontFlagsWideCodes'] = $this->io->collectUB(1);

        if ($version == 1) {
            $ret['codeTable'] = array();
            while ($this->io->bytePos < $bytePosEnd) {
                $ret['codeTable'][] = $ret['fontFlagsWideCodes'] ? $this->io->collectUI16() : $this->io->collectUI8();
            }
        } else if ($version == 2) {
            $ret['languageCode'] = $this->io->collectUI8();

            $ret['codeTable'] = array();
            while ($this->io->bytePos < $bytePosEnd) {
                $ret['codeTable'][] = $this->io->collectUI16();
            }
        }
        return $ret;
    }

    private function parseDefineSoundTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineSound';
        $ret['soundId'] = $this->io->collectUI16();
        $ret['soundFormat'] = $this->io->collectUB(4);
        $ret['soundRate'] = $this->io->collectUB(2);
        $ret['soundSize'] = $this->io->collectUB(1);
        $ret['soundType'] = $this->io->collectUB(1);
        $ret['soundSampleCount'] = $this->io->collectUI32();
        $ret['soundData'] = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);
        return $ret;
    }

    private function parseStartSoundTag($bytePosEnd, $version) {
        $ret = array();
        $ret['tagType'] = $version == 1 ? 'StartSound' : 'StartSound2';
        if ($version == 1) {
            $ret['soundId'] = $this->io->collectUI16();
        } else if ($version == 2) {
            $ret['soundClassName'] = $this->io->collectString();
        }
        $ret['soundInfo'] = $this->rec->collectSoundInfo();
        return $ret;
    }

    private function parseDefineButtonSoundTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineButtonSound';
        $ret['buttonId'] = $this->io->collectUI16();
        $ret['buttonSoundChar0'] = $this->io->collectUI16();
        if ($ret['buttonSoundChar0'] != 0) {
            $ret['buttonSoundInfo0'] = $this->rec->collectSoundInfo();
        }
        $ret['buttonSoundChar1'] = $this->io->collectUI16();
        if ($ret['buttonSoundChar1'] != 0) {
            $ret['buttonSoundInfo1'] = $this->rec->collectSoundInfo();
        }
        $ret['buttonSoundChar2'] = $this->io->collectUI16();
        if ($ret['buttonSoundChar2'] != 0) {
            $ret['buttonSoundInfo2'] = $this->rec->collectSoundInfo();
        }
        $ret['buttonSoundChar3'] = $this->io->collectUI16();
        if ($ret['buttonSoundChar3'] != 0) {
            $ret['buttonSoundInfo3'] = $this->rec->collectSoundInfo();
        }
        return $ret;
    }

    private function parseSoundStreamHeadTag($bytePosEnd, $version) {
        $ret = array();
        $ret['tagType'] = $version == 1 ? 'SoundStreamHead' : 'SoundStreamHead2';
        $this->io->collectUB(4); // Reserved
        $ret['playbackSoundRate'] = $this->io->collectUB(2);
        $ret['playbackSoundSize'] = $this->io->collectUB(1);
        $ret['playbackSoundType'] = $this->io->collectUB(1);

        $ret['streamSoundCompression'] = $this->io->collectUB(4);
        $ret['streamSoundRate'] = $this->io->collectUB(2);
        $ret['streamSoundSize'] = $this->io->collectUB(1);
        $ret['streamSoundType'] = $this->io->collectUB(1);

        $ret['streamSoundSampleCount'] = $this->io->collectUI16();
        if ($ret['streamSoundCompression'] == 2) { // MP3
            $ret['latencySeek'] = $this->io->collectSI16();
        }
        return $ret;
    }

    private function parseSoundStreamBlockTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'SoundStreamBlock';
        $ret['soundStreamData'] = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);
        return $ret;
    }

    private function parseDefineBitsLosslessTag($bytePosEnd, $version) {
        $ret = array();
        $ret['tagType'] = $version == 1 ? 'DefineBitsLossless' : 'DefineBitsLossless2';
        $ret['characterId'] = $this->io->collectUI16();
        $ret['bitmapFormat'] = $this->io->collectUI8();
        $ret['bitmapWidth'] = $this->io->collectUI16();
        $ret['bitmapHeight'] = $this->io->collectUI16();
        if ($ret['bitmapFormat'] == 3) {
            $colors = $this->io->collectUI8();
        }
        $data = gzuncompress($this->io->collectBytes($bytePosEnd - $this->io->bytePos)); // ZLIB uncompress
        if ($ret['bitmapFormat'] == 3) {
            // Colormap
            if ($version == 1) {
                $colorTableSize = 3 * ($colors + 1); // 3 bytes per RGB value
            } else if ($version == 2) {
                $colorTableSize = 4 * ($colors + 1); // 4 bytes per RGBA value
            }
            $ret['colorTable'] = substr($data, 0, $colorTableSize);
            $ret['pixelData'] = substr($data, $colorTableSize);
        } else if ($ret['bitmapFormat'] == 4 || $ret['bitmapFormat'] == 5) {
            $ret['pixelData'] = $data;
        } else {
            throw new Exception(sprintf('Internal error: bitmapFormat=%d', $ret['bitmapFormat']));
        }
        return $ret;
    }

    private function parseDefineBitsJPEGTag($bytePosEnd, $version) {
        $ret = array();
        if ($version == 2) {
            $ret['tagType'] = 'DefineBitsJPEG2';
            $ret['characterId'] = $this->io->collectUI16();
            $ret['imageData'] = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);
        } else if ($version == 3) {
            $ret['tagType'] = 'DefineBitsJPEG3';
            $ret['characterId'] = $this->io->collectUI16();
            $alphaDataOffset = $this->io->collectUI32();
            $ret['imageData'] = $this->io->collectBytes($alphaDataOffset);
            $ret['alphaData'] = gzuncompress($this->io->collectBytes($bytePosEnd - $this->io->bytePos)); // ZLIB uncompress alpha channel
        } else if ($version == 4) {
            $ret['tagType'] = 'DefineBitsJPEG4';
            $ret['characterId'] = $this->io->collectUI16();
            $alphaDataOffset = $this->io->collectUI32();
            $ret['deblockParam'] = $this->io->collectUI16();
            $ret['imageData'] = $this->io->collectBytes($alphaDataOffset);
            $ret['alphaData'] = gzuncompress($this->io->collectBytes($bytePosEnd - $this->io->bytePos)); // ZLIB uncompress alpha channel
        } else {
            throw new Exception(sprintf('Internal error: version=%d', $version));
        }
        return $ret;
    }

    private function parseDefineButtonCxformTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineButtonCxform';
        $ret['buttonId'] = $this->io->collectUI16();
        $ret['colorTransform'] = $this->rec->collectColorTransform(false);
        return $ret;
    }

    private function parseProtectTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'Protect';
        //XXX a MD5 password may be present here
        return $ret;
    }

    private function parsePlaceObject2Tag($bytePosEnd, $swfVersion) {
        $ret = array();
        $ret['tagType'] = 'PlaceObject2';

        $ret['placeFlagHasClipActions'] = $this->io->collectUB(1);
        $ret['placeFlagHasClipDepth'] = $this->io->collectUB(1);
        $ret['placeFlagHasName'] = $this->io->collectUB(1);
        $ret['placeFlagHasRatio'] = $this->io->collectUB(1);
        $ret['placeFlagHasColorTransform'] = $this->io->collectUB(1);
        $ret['placeFlagHasMatrix'] = $this->io->collectUB(1);
        $ret['placeFlagHasCharacter'] = $this->io->collectUB(1);
        $ret['placeFlagMove'] = $this->io->collectUB(1);

        $ret['depth'] = $this->io->collectUI16();
        if ($ret['placeFlagHasCharacter'] != 0) {
            $ret['characterId'] = $this->io->collectUI16();
        }
        if ($ret['placeFlagHasMatrix'] != 0) {
            $ret['matrix'] = $this->rec->collectMatrix();
        }
        if ($ret['placeFlagHasColorTransform'] != 0) {
            $ret['colorTransform'] = $this->rec->collectColorTransform(true);
        }
        if ($ret['placeFlagHasRatio'] != 0) {
            $ret['ratio'] = $this->io->collectUI16();
        }
        if ($ret['placeFlagHasName'] != 0) {
            $ret['name'] = $this->io->collectString();
        }
        if ($ret['placeFlagHasClipDepth'] != 0) {
            $ret['clipDepth'] = $this->io->collectUI16();
        }
        if ($ret['placeFlagHasClipActions'] != 0) {
            $ret['clipActions'] = $this->rec->collectClipActions($swfVersion);
        }
        return $ret;
    }

    private function parseDefineEditTextTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineEditText';
        $ret['characterId'] = $this->io->collectUI16();
        $ret['bounds'] = $this->rec->collectRect();

        $ret['hasText'] = $this->io->collectUB(1);
        $ret['wordWrap'] = $this->io->collectUB(1);
        $ret['multiline'] = $this->io->collectUB(1);
        $ret['password'] = $this->io->collectUB(1);
        $ret['readOnly'] = $this->io->collectUB(1);
        $ret['hasTextColor'] = $this->io->collectUB(1);
        $ret['hasMaxLength'] = $this->io->collectUB(1);
        $ret['hasFont'] = $this->io->collectUB(1);

        $ret['hasFontClass'] = $this->io->collectUB(1);
        $ret['autoSize'] = $this->io->collectUB(1);
        $ret['hasLayout'] = $this->io->collectUB(1);
        $ret['noSelect'] = $this->io->collectUB(1);
        $ret['border'] = $this->io->collectUB(1);
        $ret['wasStatic'] = $this->io->collectUB(1);
        $ret['HTML'] = $this->io->collectUB(1);
        $ret['useOutlines '] = $this->io->collectUB(1);

        if ($ret['hasFont'] != 0) {
            $ret['fontId'] = $this->io->collectUI16();
        }
        if ($ret['hasFontClass'] != 0) {
            $ret['fontClass'] = $this->io->collectString();
        }
        if ($ret['hasFont'] != 0) {
            $ret['fontHeight'] = $this->io->collectUI16();
        }
        if ($ret['hasTextColor'] != 0) {
            $ret['textColor'] = $this->rec->collectRGBA();
        }
        if ($ret['hasMaxLength'] != 0) {
            $ret['maxLength'] = $this->io->collectUI16();
        }
        if ($ret['hasLayout'] != 0) {
            $ret['align'] = $this->io->collectUI8();
            $ret['leftMargin'] = $this->io->collectUI16();
            $ret['rightMargin'] = $this->io->collectUI16();
            $ret['indent'] = $this->io->collectUI16();
            $ret['leading'] = $this->io->collectSI16();
        }
        $ret['variableName'] = $this->io->collectString();
        if ($ret['hasText'] != 0) {
            $ret['initialText'] = $this->io->collectString();
        }
        return $ret;
    }

    private function parseDefineSpriteTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineSprite';
        $ret['spriteId'] = $this->io->collectUI16();
        $ret['frameCount'] = $this->io->collectUI16();
        $b = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);

        $io = new SWFio($b);
        $rec = new SWFrec($io);
        $tag = new SWFtag($io, $rec, $this->swfVersion);

        // Collect and parse tags
        $ret['tags'] = array();
        while ($io->bytePos < strlen($io->b)) {
            $recordHeader = $io->collectUI16();
            $tagType = $recordHeader >> 6;
            $tagLength = $recordHeader & 0x3f;
            if ($tagLength == 0x3f) {
                $tagLength = $io->collectSI32();
            }
            $bytePosEnd = $io->bytePos + $tagLength;
            $ret['tags'][] = $tag->parseTag(array('type' => $tagType, 'offset' => $io->bytePos, 'length' => $tagLength));
            $io->bytePos = $bytePosEnd;
        }
        return $ret;
    }

    private function parseFrameLabelTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'FrameLabel';
        $ret['frameLabel'] = $this->io->collectString();
        return $ret;
    }

    private function parseDefineMorphShapeTag($bytePosEnd, $version) {
        $ret = array();
        $ret['tagType'] = $version == 1 ? 'DefineMorphShape' : 'DefineMorphShape2';
        $ret['characterId'] = $this->io->collectUI16();
        $ret['startBounds'] = $this->rec->collectRect();
        $ret['endBounds'] = $this->rec->collectRect();
        if ($version == 2) {
            $ret['startEdgeBounds'] = $this->rec->collectRect();
            $ret['endEdgeBounds'] = $this->rec->collectRect();
            $this->io->collectUB(6); // Reserved
            $ret['[usesNonScalingStrokes'] = $this->io->collectUB(1);
            $ret['usesScalingStrokes'] = $this->io->collectUB(1);
        }
        $ret['offset'] = $this->io->collectUI32();
        $ret['morphFillStyles'] = $this->rec->collectMorphFillStyleArray();
        $ret['morphLineStyles'] = $this->rec->collectMorphLineStyleArray($version);
        $ret['startEdges'] = $this->rec->collectShape(1);
        $ret['endEdges'] = $this->rec->collectShape(1);
        return $ret;
    }

    private function parseDefineFont23Tag($bytePosEnd, $version) {
        $ret = array();
        $ret['tagType'] = $version == 2 ? 'DefineFont2' : 'DefineFont3';
        $ret['fontId'] = $this->io->collectUI16();
        $ret['fontFlagsHasLayout'] = $this->io->collectUB(1);
        $ret['fontFlagsShiftJIS'] = $this->io->collectUB(1);
        $ret['fontFlagsSmallText'] = $this->io->collectUB(1);
        $ret['fontFlagsANSI'] = $this->io->collectUB(1);
        $ret['fontFlagsWideOffsets'] = $this->io->collectUB(1);
        $ret['fontFlagsWideCodes'] = $this->io->collectUB(1);
        $ret['fontFlagsItalic'] = $this->io->collectUB(1);
        $ret['fontFlagsBold'] = $this->io->collectUB(1);
        $ret['languageCode'] = $this->io->collectUI8();
        $fontNameLength = $this->io->collectUI8();
        $ret['fontName'] = substr($this->io->collectBytes($fontNameLength), 0, -1); // Remove trailing NULL
        $ret['numGlyphs'] = $this->io->collectUI16();

        $numGlyphs = $ret['numGlyphs'];
        $fontFlagsHasLayout = $ret['fontFlagsHasLayout'] != 0;
        $fontFlagsWideOffsets = $ret['fontFlagsWideOffsets'] != 0;
        $fontFlagsWideCodes = $ret['fontFlagsWideCodes'] != 0;
        $offsetTable = array();
        for ($i = 0; $i < $numGlyphs; $i++) {
            $offsetTable[] = $fontFlagsWideOffsets ? $this->io->collectUI32() : $this->io->collectUI16();
        }
        $codeTableOffset = $fontFlagsWideOffsets ? $this->io->collectUI32() : $this->io->collectUI16();
        $ret['glyphShapeTable'] = array();
        for ($i = 0; $i < $numGlyphs; $i++) {
            $ret['glyphShapeTable'][] = $this->rec->collectShape(1);
        }
        $ret['codeTable'] = array();
        for ($i = 0; $i < $numGlyphs; $i++) {
            if ($version == 2) {
                $ret['codeTable'][] = $fontFlagsWideCodes ? $this->io->collectUI16() : $this->io->collectUI8();
            } else if ($version == 3) {
                $ret['codeTable'][] = $this->io->collectUI16();
            }
        }

        if ($fontFlagsHasLayout) {
            $ret['fontAscent'] = $this->io->collectSI16();
            $ret['fontDescent'] = $this->io->collectSI16();
            $ret['fontLeading'] = $this->io->collectSI16();
            $ret['fontAdvanceTable'] = array();
            for ($i = 0; $i < $numGlyphs; $i++) {
                $ret['fontAdvanceTable'][] = $this->io->collectSI16();
            }
            $ret['fontBoundsTable'] = array();
            for ($i = 0; $i < $numGlyphs; $i++) {
                $ret['fontBoundsTable'][] = $this->rec->collectRect();
            }
            $kerningCount = $this->io->collectUI16();
            $ret['fontKerningTable'] = array();
            for ($i = 0; $i < $kerningCount; $i++) {
                $fontKerningCode1 = $fontFlagsWideCodes ? $this->io->collectUI16() : $this->io->collectUI8();
                $fontKerningCode2 = $fontFlagsWideCodes ? $this->io->collectUI16() : $this->io->collectUI8();
                $fontKerningAdjustment = $this->io->collectSI16();
                $ret['fontKerningTable'][] =
                    array('fontKerningCode1' => $fontKerningCode1,
                        'fontKerningCode2' => $fontKerningCode2,
                        'fontKerningAdjustment' => $fontKerningAdjustment);
            }
        }
        return $ret;
    }

    private function parseExportAssetsTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'ExportAssets';
        $ret['tags'] = array();
        $ret['names'] = array();
        $count = $this->io->collectUI16();
        for ($i = 0; $i < $count; $i++) {
            $ret['tags'][] = $this->io->collectUI16();
            $ret['names'][] = $this->io->collectString();
        }
        return $ret;
    }

    private function parseImportAssetsTag($bytePosEnd, $version) {
        $ret = array();
        $ret['tagType'] = $version == 1 ? 'ImportAssets' : 'ImportAssets2';
        $ret['URL'] = $this->io->collectString();
        if ($version == 2) {
            $this->io->collectUI8(); // Reserved, must be 1
            $this->io->collectUI8(); // Reserved, must be 0
        }
        $ret['tags'] = array();
        $ret['names'] = array();
        $count = $this->io->collectUI16();
        for ($i = 0; $i < $count; $i++) {
            $ret['tags'][] = $this->io->collectUI16();
            $ret['names'][] = $this->io->collectString();
        }
        return $ret;
    }

    private function parseEnableDebuggerTag($bytePosEnd, $version) {
        $ret = array();
        $ret['tagType'] = $version == 1 ? 'EnableDebugger' : 'EnableDebugger2';
        if ($version == 2) {
            $this->io->collectUI16(); // Reserved, must be 0
        }
        $ret['password'] = $this->io->collectString();
        return $ret;
    }

    private function parseDoInitActionTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DoInitAction';
        $ret['spriteId'] = $this->io->collectUI16();
        $ret['actions'] = $this->rec->collectActionRecords($bytePosEnd);
        return $ret;
    }

    private function parseDefineVideoStreamTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineVideoStream';
        $ret['characterId'] = $this->io->collectUI16();
        $ret['numFrames'] = $this->io->collectUI16();
        $ret['width'] = $this->io->collectUI16();
        $ret['height'] = $this->io->collectUI16();

        $this->io->collectUB(4); // Reserved
        $ret['videoFlagsDeblocking'] = $this->io->collectUB(3);
        $ret['videoFlagsSmoothing'] = $this->io->collectUB(1);

        $ret['codecId'] = $this->io->collectUI8();
        return $ret;
    }

    private function parseVideoFrameTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'VideoFrame';
        $ret['streamId'] = $this->io->collectUI16();
        $ret['frameNum'] = $this->io->collectUI16();
        $ret['videoData'] = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);
        return $ret;
    }

    private function parseScriptLimitsTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'ScriptLimits';
        $ret['maxRecursionDepth'] = $this->io->collectUI16();
        $ret['scriptTimeoutSeconds'] = $this->io->collectUI16();
        return $ret;
    }

    private function parseSetTabIndexTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'SetTabIndex';
        $ret['depth'] = $this->io->collectUI16();
        $ret['tabIndex'] = $this->io->collectUI16();
        return $ret;
    }

    private function parseFileAttributesTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'FileAttributes';
        $this->io->collectUB(1); // Reserved
        $ret['useDirectBlit'] = $this->io->collectUB(1);
        $ret['useGPU'] = $this->io->collectUB(1);
        $ret['hasMetadata'] = $this->io->collectUB(1);
        $ret['actionScript3'] = $this->io->collectUB(1);
        $this->io->collectUB(2); // Reserved
        $ret['useNetwork'] = $this->io->collectUB(1);
        $this->io->collectUB(24); // Reserved
        return $ret;
    }

    private function parsePlaceObject3Tag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'PlaceObject3';

        $ret['placeFlagHasClipActions'] = $this->io->collectUB(1);
        $ret['placeFlagHasClipDepth'] = $this->io->collectUB(1);
        $ret['placeFlagHasName'] = $this->io->collectUB(1);
        $ret['placeFlagHasRatio'] = $this->io->collectUB(1);
        $ret['placeFlagHasColorTransform'] = $this->io->collectUB(1);
        $ret['placeFlagHasMatrix'] = $this->io->collectUB(1);
        $ret['placeFlagHasCharacter'] = $this->io->collectUB(1);
        $ret['placeFlagMove'] = $this->io->collectUB(1);

        $this->io->collectUB(3); // Reserved, must be 0
        $ret['placeFlagHasImage'] = $this->io->collectUB(1);
        $ret['placeFlagHasClassName'] = $this->io->collectUB(1);
        $ret['placeFlagHasCacheAsBitmap'] = $this->io->collectUB(1);
        $ret['placeFlagHasBlendMode'] = $this->io->collectUB(1);
        $ret['placeFlagHasFilterList'] = $this->io->collectUB(1);

        $ret['depth'] = $this->io->collectUI16();
        if ($ret['placeFlagHasClassName'] != 0 || ($ret['placeFlagHasImage'] != 0 && $ret['placeFlagHasCharacter'] != 0)) {
            $ret['className'] = $this->io->collectString();
        }
        if ($ret['placeFlagHasCharacter'] != 0) {
            $ret['characterId'] = $this->io->collectUI16();
        }
        if ($ret['placeFlagHasMatrix'] != 0) {
            $ret['matrix'] = $this->rec->collectMatrix();
        }
        if ($ret['placeFlagHasColorTransform'] != 0) {
            $ret['colorTransform'] = $this->rec->collectColorTransform(true);
        }
        if ($ret['placeFlagHasRatio'] != 0) {
            $ret['ratio'] = $this->io->collectUI16();
        }
        if ($ret['placeFlagHasName'] != 0) {
            $ret['name'] = $this->io->collectString();
        }
        if ($ret['placeFlagHasClipDepth'] != 0) {
            $ret['clipDepth'] = $this->io->collectUI16();
        }
        if ($ret['placeFlagHasFilterList'] != 0) {
            $ret['surfaceFilterlist'] = $this->rec->collectFilterList();
        }
        if ($ret['placeFlagHasBlendMode'] != 0) {
            $ret['blendMode'] = $this->io->collectUI8();
        }
        if ($ret['placeFlagHasCacheAsBitmap'] != 0) {
            $ret['bitmapCache'] = $this->io->collectUI8();
        }
        if ($ret['placeFlagHasClipActions'] != 0) {
            $ret['clipActions'] = $this->rec->collectClipActions($this->swfVersion);
        }
        return $ret;
    }

    private function parseDefineFontAlignZonesTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineFontAlignZones';
        $ret['fontId'] = $this->io->collectUI16();
        $ret['CSMTableHint'] = $this->io->collectUB(2);
        $this->io->collectUB(6); // Reserved
        $ret['zoneTable'] = $this->rec->collectZoneTable($bytePosEnd);
        return $ret;
    }

    private function parseCSMTextSettingsTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'CSMTextSettings';
        $ret['textId'] = $this->io->collectUI16();
        $ret['useFlashType'] = $this->io->collectUB(2);
        $ret['gridFit'] = $this->io->collectUB(3);
        $this->io->collectUB(3); // Reserved
        $ret['thickness'] = $this->io->collectFixed(); //XXX F32 in the spec
        $ret['sharpness'] = $this->io->collectFixed(); //XXX F32 in the spec
        $this->io->collectUI8(); // Reserved
        return $ret;
    }

    private function parseSymbolClassTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'SymbolClass';
        $numSymbols = $this->io->collectUI16();
        $tags = array();
        $names = array();
        for ($i = 0; $i < $numSymbols; $i++) {
            $tags[] = $this->io->collectUI16();
            $names[] = $this->io->collectString();
        }
        $ret['tags'] = $tags;
        $ret['names'] = $names;
        return $ret;
    }

    private function parseMetadataTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'Metadata';
        $ret['metadata'] = $this->io->collectString();
        return $ret;
    }

    private function parseDefineScalingGridTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineScalingGrid';
        $ret['characterId'] = $this->io->collectUI16();
        $ret['splitter'] = $this->rec->collectRect();
        return $ret;
    }

    private function parseDoABCTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DoABC';
        $ret['flags'] = $this->io->collectUI32();
        $ret['name'] = $this->io->collectString();
        $ret['ABCdata'] = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);
        return $ret;
    }

    private function parseDefineSceneAndFrameLabelDataTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineSceneAndFrameLabelData';

        $sceneOffsets = array();
        $sceneNames = array();
        $sceneCount = $this->io->collectEncodedU32();
        for ($i = 0; $i < $sceneCount; $i++) {
            $sceneOffsets[] = $this->io->collectEncodedU32();
            $sceneNames[] = $this->io->collectString();
        }
        $ret['sceneOffsets'] = $sceneOffsets;
        $ret['sceneNames'] = $sceneNames;

        $frameNumbers = array();
        $frameLabels = array();
        $frameLabelCount = $this->io->collectEncodedU32();
        for ($i = 0; $i < $frameLabelCount; $i++) {
            $frameNumbers[] = $this->io->collectEncodedU32();
            $frameLabels[] = $this->io->collectString();
        }
        $ret['frameNumbers'] = $frameNumbers;
        $ret['frameLabels'] = $frameLabels;

        return $ret;
    }

    private function parseDefineBinaryDataTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineBinaryData';
        $ret['tag'] = $this->io->collectUI16();
        $this->io->collectUI32(); // Reserved, must be 0
        $ret['data'] = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);
        return $ret;
    }

    private function parseDefineFontNameTag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineFontName';
        $ret['fontId'] = $this->io->collectUI16();
        $ret['fontName'] = $this->io->collectString();
        $ret['fontCopyright'] = $this->io->collectString();
        return $ret;
    }

    private function parseDefineFont4Tag($bytePosEnd) {
        $ret = array();
        $ret['tagType'] = 'DefineFont4';
        $ret['fontId'] = $this->io->collectUI16();

        $this->io->collectUB(5); // Reserved
        $ret['fontFlagsHasFontData'] = $this->io->collectUB(1);
        $ret['fontFlagsItalic'] = $this->io->collectUB(1);
        $ret['fontFlagsBold'] = $this->io->collectUB(1);

        $ret['fontName'] = $this->io->collectString();

        if ($ret['fontFlagsHasFontData'] == 1) {
            $ret['fontData'] = $this->io->collectBytes($bytePosEnd - $this->io->bytePos);
        }
        return $ret;
    }
}

class SWFrec {
    private $io; // SWF for basic I/O

    public function __construct($io) {
        $this->io = $io;
    }

    public function collectRGB() {
        $ret = array();
        $ret['red'] = $this->io->collectUI8();
        $ret['green'] = $this->io->collectUI8();
        $ret['blue'] = $this->io->collectUI8();
        return $ret;
    }

    public function collectRGBA() {
        $ret = array();
        $ret['red'] = $this->io->collectUI8();
        $ret['green'] = $this->io->collectUI8();
        $ret['blue'] = $this->io->collectUI8();
        $ret['alpha'] = $this->io->collectUI8();
        return $ret;
    }

    public function collectRect() {
        $ret = array();
        $nbits = $this->io->collectUB(5);
        $ret['xmin'] = $this->io->collectSB($nbits);
        $ret['xmax'] = $this->io->collectSB($nbits);
        $ret['ymin'] = $this->io->collectSB($nbits);
        $ret['ymax'] = $this->io->collectSB($nbits);
        $this->io->byteAlign();
        return $ret;
    }

    public function collectMatrix() {
        $ret = array();
        if (($hasScale = $this->io->collectUB(1)) != 0) {
            $nScaleBits = $this->io->collectUB(5);
            $ret['scaleX'] = $this->io->collectFB($nScaleBits);
            $ret['scaleY'] = $this->io->collectFB($nScaleBits);
        }
        if (($hasRotate = $this->io->collectUB(1)) != 0) {
            $nRotateBits = $this->io->collectUB(5);
            $ret['rotateSkew0'] = $this->io->collectFB($nRotateBits);
            $ret['rotateSkew1'] = $this->io->collectFB($nRotateBits);
        }
        if (($nTranslateBits = $this->io->collectUB(5)) != 0) {
            $ret['translateX'] = $this->io->collectSB($nTranslateBits);
            $ret['translateY'] = $this->io->collectSB($nTranslateBits);
        }
        $this->io->byteAlign();
        return $ret;
    }

    public function collectColorTransform($withAlpha) {
        $colorTransform = array();

        $hasAddTerms = $this->io->collectUB(1);
        $hasMultTerms = $this->io->collectUB(1);
        $nbits = $this->io->collectUB(4);
        if ($hasMultTerms != 0) {
            $colorTransform['redMultTerm'] = $this->io->collectSB($nbits);
            $colorTransform['greenMultTerm'] = $this->io->collectSB($nbits);
            $colorTransform['blueMultTerm'] = $this->io->collectSB($nbits);
            if ($withAlpha) {
                $colorTransform['alphaMultTerm'] = $this->io->collectSB($nbits);
            }
        }
        if ($hasAddTerms != 0) {
            $colorTransform['redAddTerm'] = $this->io->collectSB($nbits);
            $colorTransform['greenAddTerm'] = $this->io->collectSB($nbits);
            $colorTransform['blueAddTerm'] = $this->io->collectSB($nbits);
            if ($withAlpha) {
                $colorTransform['alphaAddTerm'] = $this->io->collectSB($nbits);
            }
        }
        $this->io->byteAlign();
        return $colorTransform;
    }

    ////////////////////////////////////////////////////////////////////////////////
    // More complex records
    ////////////////////////////////////////////////////////////////////////////////

    private $actionNames = array
    (// SWF 3
        0x81 => 'ActionGotoFrame',
        0x83 => 'ActionGetURL',
        0x04 => 'ActionNextFrame',
        0x05 => 'ActionPreviousFrame',
        0x06 => 'ActionPlay',
        0x07 => 'ActionStop',
        0x08 => 'ActionToggleQuality',
        0x09 => 'ActionStopSounds',
        0x8a => 'ActionWaitForFrame',
        0x8b => 'ActionSetTarget',
        0x8c => 'ActionGoToLabel',
        // SWF 4
        0x96 => 'ActionPush', // Stack operations
        0x17 => 'ActionPop',
        0x0a => 'ActionAdd', // Arithmetic operators
        0x0b => 'ActionSubtract',
        0x0c => 'ActionMultiply',
        0x0d => 'ActionDivide',
        0x0e => 'ActionEquals', // Numerical comparison
        0x0f => 'ActionLess',
        0x10 => 'ActionAnd', // Logical operators
        0x11 => 'ActionOr',
        0x12 => 'ActionNot',
        0x13 => 'ActionStringEquals', // String manipulation
        0x14 => 'ActionStringLength',
        0x21 => 'ActionStringAdd',
        0x15 => 'ActionStringExtract',
        0x29 => 'ActionStringLess',
        0x31 => 'ActionMBStringLength',
        0x35 => 'ActionMBStringExtract',
        0x18 => 'ActionToInteger', // Type conversion
        0x32 => 'ActionCharToAscii',
        0x33 => 'ActionAsciiToChar',
        0x36 => 'ActionMBCharToAscii',
        0x37 => 'ActionMBAsciiToChar',
        0x99 => 'ActionJump', // Control flow
        0x9d => 'ActionIf',
        0x9e => 'ActionCall',
        0x1c => 'ActionGetVariable', // Variables
        0x1d => 'ActionSetVariable',
        0x9a => 'ActionGetURL2', // Movie control
        0x9f => 'ActionGotoFrame2',
        0x20 => 'ActionSetTarget2',
        0x22 => 'ActionGetProperty',
        0x23 => 'ActionSetProperty',
        0x24 => 'ActionCloneSprite',
        0x25 => 'ActionRemoteSprite',
        0x27 => 'ActionStartDrag',
        0x28 => 'ActionEndDrag',
        0x8d => 'ActionWaitForFrame2',
        0x26 => 'ActionTrace', // Utilities
        0x34 => 'ActionGetTime',
        0x30 => 'ActionRandomNumber',
        // SWF 5
        0x3d => 'ActionCallFunction', // ScriptObject actions
        0x52 => 'ActionCallMethod',
        0x88 => 'ActionConstantPool',
        0x9b => 'ActionDefineFunction',
        0x3c => 'ActionDefineLocal',
        0x41 => 'ActionDefineLocal2',
        0x3a => 'ActionDelete',
        0x3b => 'ActionDelete2',
        0x46 => 'ActionEnumerate',
        0x49 => 'ActionEquals2',
        0x4e => 'ActionGetMember',
        0x42 => 'ActionInitArray',
        0x43 => 'ActionInitObject',
        0x53 => 'ActionNewMethod',
        0x40 => 'ActionNewObject',
        0x4f => 'ActionSetMember',
        0x45 => 'ActionTargetPath',
        0x94 => 'ActionWith',
        0x4a => 'ActionToNumber', // Type actions
        0x4b => 'ActionToString',
        0x44 => 'ActionTypeOf',
        0x47 => 'ActionAdd2', // Math actions
        0x48 => 'ActionLess2',
        0x3f => 'ActionModule',
        0x60 => 'ActionBitAnd', // Stack operator actions
        0x63 => 'ActionBitLShift',
        0x61 => 'ActionBitOr',
        0x64 => 'ActionBitRShift',
        0x65 => 'ActionBitURShift',
        0x62 => 'ActionBitXor',
        0x51 => 'ActionDecrement',
        0x50 => 'ActionIncrement',
        0x4c => 'ActionPushDuplicate',
        0x3e => 'ActionReturn',
        0x4d => 'ActionStackSwap',
        0x87 => 'ActionStoreRegister',
        // SWF 6
        0x59 => 'DoInitAction',
        0x54 => 'ActionInstanceOf',
        0x55 => 'ActionEnumerate2',
        0x66 => 'ActionStrictEquals',
        0x67 => 'ActionGreater',
        0x68 => 'ActionStringGreater',
        // SWF 7
        0x8e => 'ActionDefineFunction2',
        0x69 => 'ActionExtends',
        0x2b => 'ActionCastOp',
        0x2c => 'ActionImplementsOp',
        0x8f => 'ActionTry',
        0x2a => 'ActionThrow',
        // SWF 9
        0x82 => 'DoABC',
        // SWF 10
    );

    private function actionRecordIsProcessed($offset, $actions) {
        foreach ($actions as $action) {
            if ($action['offset'] == $offset) {
                return true;
            }
        }
        return false;
    }

    public function collectActionRecords($bytePosEnd) {
        $actions = array();
        for (;;) {
            if ($this->io->bytePos >= $bytePosEnd) {
                break;
            }
            $offset = $this->io->bytePos;
            $actionLength = 0;
            $actionName = null;
            $actionData = null;
            if (($actionCode = $this->io->collectUI8()) == 0) {
                // echo sprintf("%6d: Code=0x%02x, breaking\n", $offset, $actionCode);
                $actions[] =
                    array('offset' => $offset,
                        'actionCode' => $actionCode,
                        'actionLength' => 0,
                        'actionName' => null,
                        'actionData' => null);
                continue; // break;
            }
            if ($actionCode >= 0x80) {
                $actionLength = $this->io->collectUI16();
            }
            if (isset($this->actionNames[$actionCode])) {
                $actionName = $this->actionNames[$actionCode];
                if ($actionLength > 0) {
                    $actionData = $this->collectActionData($actionCode, $actionLength);
                }
                // echo sprintf("%6d: Code=0x%02x, length=%d, name=%s\n", $offset, $actionCode, $actionLength, $actionName);
                $actions[] =
                    array('offset' => $offset,
                        'actionCode' => $actionCode,
                        'actionLength' => $actionLength,
                        'actionName' => $actionName,
                        'actionData' => $actionData);
            } else {
                throw new Exception(sprintf("Internal error: actionCode=0x%02X, actionLength=%d", $actionCode, $actionLength));
            }
        }
        if ($this->io->bytePos != $bytePosEnd) {
            echo sprintf("\n\nTHANOS THERE ARE %d bytes left\n", $bytePosEnd - $this->io->bytePos);
            // throw new Exception("OOPS");
        }
        // var_dump($actions);
        return $actions;
    }

    public function collectActionData($actionCode, $actionLength) {
        $actionData = array();
        if ($actionCode == 0x81) { // ActionGotoFrame
            $actionData['frame'] = $this->io->collectUI16();
        } else if ($actionCode == 0x83) { // ActionGetURL
            $actionData['url'] = $this->io->collectString();
            $actionData['target'] = $this->io->collectString();
        } else if ($actionCode == 0x87) { // ActionStoreRegister
            $actionData['registerNumber'] = $this->io->collectUI8();
        } else if ($actionCode == 0x88) { // ActionConstantPool
            $count = $this->io->collectUI16();
            for ($i = 0; $i < $count; $i++) {
                $actionData[sprintf('constant_%d', $i)] = $this->io->collectString();
            }
        } else if ($actionCode == 0x8A) { // ActionWaitForFrame
            $actionData['frame'] = $this->io->collectUI16();
            $actionData['skipCount'] = $this->io->collectUI8();
        } else if ($actionCode == 0x8B) { // ActionSetTarget
            $actionData['targetName'] = $this->io->collectString();
        } else if ($actionCode == 0x8C) { // ActionGoToLabel
            $actionData['label'] = $this->io->collectString();
        } else if ($actionCode == 0x8D) { // ActionWaitForFrame2
            $actionData['skipCount'] = $this->io->collectUI8();
        } else if ($actionCode == 0x8E) { // ActionDefineFunction2
            $actionData['functionName'] = $this->io->collectString();
            $numParams = $this->io->collectUI16();
            $actionData['registerCount'] = $this->io->collectUI8();

            $actionData['preloadParentFlag'] = $this->io->collectUB(1);
            $actionData['preloadRootFlag'] = $this->io->collectUB(1);
            $actionData['suppressSuperFlag'] = $this->io->collectUB(1);
            $actionData['preloadSuperFlag'] = $this->io->collectUB(1);
            $actionData['suppressArgumentsFlag'] = $this->io->collectUB(1);
            $actionData['preloadArgumentsFlag'] = $this->io->collectUB(1);
            $actionData['suppressThisFlag'] = $this->io->collectUB(1);
            $actionData['preloadThisFlag'] = $this->io->collectUB(1);

            $this->io->collectUB(7); // Reserved
            $actionData['preloadGlobalFlag'] = $this->io->collectUB(1);

            for ($i = 0; $i < $numParams; $i++) {
                $actionData[sprintf('register_%d', $i)] = $this->io->collectUI8();
                $actionData[sprintf('param_%d', $i)] = $this->io->collectString();
            }
            $actionData['codeSize'] = $this->io->collectUI16();
        } else if ($actionCode == 0x94) { // ActionWith
            $size = $this->io->collectUI16();
            $actionData['code'] = $this->io->collectBytes($size);
        } else if ($actionCode == 0x96) { // ActionPush
            $bytePosEnd = $this->io->bytePos + $actionLength;
            while ($this->io->bytePos < $bytePosEnd) {
                $type = $this->io->collectUI8();
                switch ($type) {
                    case 0: // String
                        $val = $this->io->collectString();
                        break;
                    case 1: // Float
                        $val = $this->io->collectFloat();
                        break;
                    case 2: // Null
                        $val = null;
                        break;
                    case 3: // Undefined
                        $val = null;
                        break;
                    case 4: // Register
                        $val = $this->io->collectUI8();
                        break;
                    case 5: // Boolean
                        $val = $this->io->collectUI8();
                        break;
                    case 6: // Double
                        $val = $this->io->collectDouble();
                        break;
                    case 7: // Integer
                        $val = $this->io->collectUI32();
                        break;
                    case 8: // Constant8
                        $val = $this->io->collectUI8();
                        break;
                    case 9: // Constant16
                        $val = $this->io->collectUI16();
                        break;
                    default:
                        throw new Exception(sprintf("Internal error: type=%d", $type));
                }
                $actionData[] = array('type' => $type, 'val' => $val);
            }
        } else if ($actionCode == 0x99 || $actionCode == 0x9D) { // ActionJump or ActionIf
            $actionData['branchOffset'] = $this->io->collectSI16();
        } else if ($actionCode == 0x9A) { // ActionGetURL2
            $actionData['sendVarsMethod'] = $this->io->collectUB(2);
            $this->io->collectUB(4); // Reserved
            $actionData['loadTargetFlag'] = $this->io->collectUB(1);
            $actionData['loadVariablesFlag'] = $this->io->collectUB(1);
        } else if ($actionCode == 0x9B) { // ActionDefineFunction
            $actionData['functionName'] = $this->io->collectString();
            $numParams = $this->io->collectUI16();
            for ($i = 0; $i < $numParams; $i++) {
                $actionData[sprintf('param_%d', $i)] = $this->io->collectString();
            }
            $actionData['codeSize'] = $this->io->collectUI16();
        } else if ($actionCode == 0x9F) { // ActionGotoFrame2
            $this->io->collectUB(6); // Reserved
            $actionData['sceneBiasFlag'] = $this->io->collectUB(1);
            $actionData['playFlag'] = $this->io->collectUB(1);
            if ($actionData['sceneBiasFlag'] == 1) {
                $actionData['sceneBias'] = $this->io->collectUI16();
            }
        } else {
            throw new Exception(sprintf("Internal error: actionCode=0x%02X, actionLength=%d", $actionCode, $actionLength));
        }
        return $actionData;
    }

    public function collectShape($shapeVersion) {
        $numFillBits = $this->io->collectUB(4);
        $numLineBits = $this->io->collectUB(4);
        $shapeRecords = $this->collectShapeRecords($shapeVersion, null, null, $numFillBits, $numLineBits);
        return $shapeRecords;
    }

    public function collectShapeWithStyle($shapeVersion) {
        $ret = array();
        $ret['fillStyles'] = $this->collectFillStyleArray($shapeVersion);
        $ret['lineStyles'] = $this->collectLineStyleArray($shapeVersion);
        $numFillBits = $this->io->collectUB(4);
        $numLineBits = $this->io->collectUB(4);
        $ret['shapeRecords'] = $this->collectShapeRecords($shapeVersion, $ret['fillStyles'], $ret['lineStyles'], $numFillBits, $numLineBits);
        return $ret;
    }

    public function collectShapeRecords($shapeVersion, $fillStyles, $lineStyles, $numFillBits, $numLineBits) {
        $shapeRecords = array();
        for (;;) {
            $typeFlag = $this->io->collectUB(1);
            if ($typeFlag == 0) {
                $stateNewStyles = $this->io->collectUB(1);
                $stateLineStyle = $this->io->collectUB(1);
                $stateFillStyle1 = $this->io->collectUB(1);
                $stateFillStyle0 = $this->io->collectUB(1);
                $stateMoveTo = $this->io->collectUB(1);
                if ($stateNewStyles == 0 && $stateLineStyle == 0 && $stateFillStyle1 == 0 && $stateFillStyle0 == 0 && $stateMoveTo == 0) {
                    // EndShapeRecord
                    $shapeRecords[] = array('type' => 'EndShapeRecord');
                    break;
                } else {
                    // StyleChangeRecord
                    $shapeRecord = array();
                    $shapeRecord['type'] = 'StyleChangeRecord';
                    $shapeRecord['stateNewStyles'] = $stateNewStyles;
                    $shapeRecord['stateLineStyle'] = $stateLineStyle;
                    $shapeRecord['stateFillStyle1'] = $stateFillStyle1;
                    $shapeRecord['stateFillStyle0'] = $stateFillStyle0;
                    $shapeRecord['stateMoveTo'] = $stateMoveTo;

                    if ($shapeRecord['stateMoveTo'] != 0) {
                        $moveBits = $this->io->collectUB(5);
                        $shapeRecord['moveDeltaX'] = $this->io->collectSB($moveBits);
                        $shapeRecord['moveDeltaY'] = $this->io->collectSB($moveBits);
                    }
                    if ($shapeRecord['stateFillStyle0'] != 0) {
                        $shapeRecord['fillStyle0'] = $this->io->collectUB($numFillBits);
                    }
                    if ($shapeRecord['stateFillStyle1'] != 0) {
                        $shapeRecord['fillStyle1'] = $this->io->collectUB($numFillBits);
                    }
                    if ($shapeRecord['stateLineStyle'] != 0) {
                        $shapeRecord['lineStyle'] = $this->io->collectUB($numLineBits);
                    }
                    if ($shapeRecord['stateNewStyles'] != 0 && ($shapeVersion == 2 || $shapeVersion == 3 || $shapeVersion == 4)) { // XXX shapeVersion 4 not in spec
                        $this->io->byteAlign();
                        $shapeRecord['fillStyles'] = $fillStyles = $this->collectFillStyleArray($shapeVersion);
                        $shapeRecord['lineStyles'] = $lineStyles = $this->collectLineStyleArray($shapeVersion);
                        $numFillBits = $this->io->collectUB(4);
                        $numLineBits = $this->io->collectUB(4);
                    }
                    $shapeRecords[] = $shapeRecord;
                }
            } else {
                $straightFlag = $this->io->collectUB(1);
                if ($straightFlag == 1) {
                    // StraightEdgeRecord
                    $shapeRecord = array();
                    $shapeRecord['type'] = 'StraightEdgeRecord';
                    $numBits = $this->io->collectUB(4);
                    $shapeRecord['generalLineFlag'] = $this->io->collectUB(1);
                    if ($shapeRecord['generalLineFlag'] == 0) {
                        $shapeRecord['vertLineFlag'] = $this->io->collectUB(1);
                    }
                    if ($shapeRecord['generalLineFlag'] == 1 || $shapeRecord['vertLineFlag'] == 0) {
                        $shapeRecord['deltaX'] = $this->io->collectSB($numBits + 2);
                    }
                    if ($shapeRecord['generalLineFlag'] == 1 || $shapeRecord['vertLineFlag'] == 1) {
                        $shapeRecord['deltaY'] = $this->io->collectSB($numBits + 2);
                    }
                    $shapeRecords[] = $shapeRecord;
                } else {
                    // CurvedEdgeRecord
                    $shapeRecord = array();
                    $shapeRecord['type'] = 'CurvedEdgeRecord';
                    $numBits = $this->io->collectUB(4);
                    $shapeRecord['controlDeltaX'] = $this->io->collectSB($numBits + 2);
                    $shapeRecord['controlDeltaY'] = $this->io->collectSB($numBits + 2);
                    $shapeRecord['anchorDeltaX'] = $this->io->collectSB($numBits + 2);
                    $shapeRecord['anchorDeltaY'] = $this->io->collectSB($numBits + 2);
                    $shapeRecords[] = $shapeRecord;
                }
            }
        }
        $this->io->byteAlign();
        return $shapeRecords;
    }

    public function collectMorphFillStyleArray() {
        $morphFillStyleArray = array();
        $fillStyleCount = $this->io->collectUI8();
        if ($fillStyleCount == 0xff) {
            $fillStyleCount = $this->io->collectUI16(); // Extended
        }
        for ($i = 0; $i < $fillStyleCount; $i++) {
            $morphFillStyleArray[] = $this->collectMorphFillStyle();
        }
        return $morphFillStyleArray;
    }

    public function collectMorphFillStyle() {
        $morphFillStyle = array(); // To return
        $morphFillStyle['fillStyleType'] = $this->io->collectUI8();
        switch($morphFillStyle['fillStyleType']) {
            case 0x00: // Solid fill
                $morphFillStyle['startColor'] = $this->collectRGBA();
                $morphFillStyle['endColor'] = $this->collectRGBA();
                break;
            case 0x10: // Linear gradient fill
            case 0x12: // Radial gradient fill
                $morphFillStyle['startGradientMatrix'] = $this->collectMatrix();
                $morphFillStyle['endGradientMatrix'] = $this->collectMatrix();
                $morphFillStyle['gradient'] = $this->collectMorphGradient();
                break;
            case 0x40: // Repeating bitmap
            case 0x41: // Clipped bitmap fill
            case 0x42: // Non-smoothed repeating bitmap
            case 0x43: // Non-smoothed clipped bitmap
                $morphFillStyle['bitmapId'] = $this->io->collectUI16();
                $morphFillStyle['startBitmapMatrix'] = $this->collectMatrix();
                $morphFillStyle['endBitmapMatrix'] = $this->collectMatrix();
                break;
            default:
                throw new Exception(sprintf('Internal error: fillStyleType=%d', $morphFillStyle['fillStyleType']));
        }
        return $morphFillStyle;
    }

    public function collectMorphGradient() {
        $morphGradient = array();
        $numGradients = $this->io->collectUI8();
        for ($i = 0; $i < $numGradients; $i++) {
            $morphGradient[] = $this->collectMorphGradientRecord();
        }
        return $morphGradient;
    }

    public function collectMorphGradientRecord() {
        $morphGradientRecord = array();
        $morphGradientRecord['startRatio'] = $this->io->collectUI8();
        $morphGradientRecord['startColor'] = $this->collectRGBA();
        $morphGradientRecord['endRatio'] = $this->io->collectUI8();
        $morphGradientRecord['endColor'] = $this->collectRGBA();
        return $morphGradientRecord;
    }

    public function collectMorphLineStyleArray($version) {
        $morphLineStyleArray = array();
        $lineStyleCount = $this->io->collectUI8();
        if ($lineStyleCount == 0xff) {
            $lineStyleCount = $this->io->collectUI16();
        }
        if ($version == 1) {
            for ($i = 0; $i < $lineStyleCount; $i++) {
                $morphLineStyleArray[] = $this->collectMorphLineStyle();
            }
        } else if ($version == 2) {
            for ($i = 0; $i < $lineStyleCount; $i++) {
                $morphLineStyleArray[] = $this->collectMorphLineStyle2();
            }
        } else {
            throw new Exception(sprintf('Internal error: version=%d', $version));
        }
        return $morphLineStyleArray;
    }

    public function collectMorphLineStyle() {
        $morphLineStyle = array(); // To return
        $morphLineStyle['startWidth'] = $this->io->collectUI16();
        $morphLineStyle['endWidth'] = $this->io->collectUI16();
        $morphLineStyle['startColor'] = $this->collectRGBA();
        $morphLineStyle['endColor'] = $this->collectRGBA();
        return $morphLineStyle;
    }

    public function collectMorphLineStyle2() {
        $morphLineStyle2 = array(); // To return
        $morphLineStyle2['startWidth'] = $this->io->collectUI16();
        $morphLineStyle2['endWidth'] = $this->io->collectUI16();

        $morphLineStyle2['startCapStyle'] = $this->io->collectUB(2);
        $morphLineStyle2['joinStyle'] = $this->io->collectUB(2);
        $morphLineStyle2['hasFillFlag'] = $this->io->collectUB(1);
        $morphLineStyle2['noHScaleFlag'] = $this->io->collectUB(1);
        $morphLineStyle2['noVScaleFlag'] = $this->io->collectUB(1);
        $morphLineStyle2['pixelHintingFlag'] = $this->io->collectUB(1);

        $this->io->collectUB(5); // Reserved
        $morphLineStyle2['noClose'] = $this->io->collectUB(1);
        $morphLineStyle2['endCapStyle'] = $this->io->collectUB(2);

        if ($morphLineStyle2['joinStyle'] == 2) {
            $morphLineStyle2['miterLimitFactor'] = $this->io->collectUI16();
        }
        if ($morphLineStyle2['hasFillFlag'] == 0) {
            $morphLineStyle2['startColor'] = $this->collectRGBA();
            $morphLineStyle2['endColor'] = $this->collectRGBA();
        }
        if ($morphLineStyle2['hasFillFlag'] == 1) {
            $morphLineStyle2['fillType'] = $this->collectMorphFillStyle();
        }
        return $morphLineStyle2;
    }

    public function collectGradient($shapeVersion) {
        $gradient = array();
        $gradient['spreadMode'] = $this->io->collectUB(2);
        $gradient['interpolationMode'] = $this->io->collectUB(2);
        $numGradientRecords = $this->io->collectUB(4);
        $gradient['gradientRecords'] = $this->collectGradientRecords($numGradientRecords, $shapeVersion);
        return $gradient;
    }

    public function collectFocalGradient($shapeVersion) { // shapeVersion must be 4
        $focalGradient = array();
        $focalGradient['spreadMode'] = $this->io->collectUB(2);
        $focalGradient['interpolationMode'] = $this->io->collectUB(2);
        $numGradientRecords = $this->io->collectUB(4);
        $focalGradient['gradientRecords'] = $this->collectGradientRecords($numGradientRecords, $shapeVersion);
        $focalGradient['focalPoint'] = $this->io->collectFixed8();
        return $focalGradient;
    }

    public function collectFilterList() {
        $filterList = array();
        $numberOfFilters = $this->io->collectUI8();
        for ($f = 0; $f < $numberOfFilters; $f++) {
            $filter = array();
            $filter['filterId'] = $this->io->collectUI8();
            switch ($filter['filterId']) {
                case 0: // DropShadowFilter
                    $filter['dropShadowColor'] = $this->collectRGBA();
                    $filter['blurX'] = $this->io->collectFixed();
                    $filter['blurY'] = $this->io->collectFixed();
                    $filter['angle'] = $this->io->collectFixed();
                    $filter['distance'] = $this->io->collectFixed();
                    $filter['strength'] = $this->io->collectFixed8();
                    $filter['innerShadow'] = $this->io->collectUB(1);
                    $filter['knockout'] = $this->io->collectUB(1);
                    $filter['compositeSource'] = $this->io->collectUB(1);
                    $filter['passes'] = $this->io->collectUB(5);
                    break;
                case 1: // BlurFilter
                    $filter['blurX'] = $this->io->collectFixed();
                    $filter['blurY'] = $this->io->collectFixed();
                    $filter['passes'] = $this->io->collectUB(5);
                    $this->io->collectUB(3); // Reserved, must be 0
                    break;
                case 2: // GlowFilter
                    $filter['glowColor'] = $this->collectRGBA();
                    $filter['blurX'] = $this->io->collectFixed();
                    $filter['blurY'] = $this->io->collectFixed();
                    $filter['strength'] = $this->io->collectFixed8();
                    $filter['innerGlow'] = $this->io->collectUB(1);
                    $filter['knockout'] = $this->io->collectUB(1);
                    $filter['compositeSource'] = $this->io->collectUB(1);
                    $filter['passes'] = $this->io->collectUB(5);
                    break;
                case 3: // BevelFilter
                    $filter['hadowColor'] = $this->collectRGBA();
                    $filter['highlightColor'] = $this->collectRGBA();
                    $filter['blurX'] = $this->io->collectFixed();
                    $filter['blurY'] = $this->io->collectFixed();
                    $filter['angle'] = $this->io->collectFixed();
                    $filter['distance'] = $this->io->collectFixed();
                    $filter['strength'] = $this->io->collectFixed8();
                    $filter['innerShadow'] = $this->io->collectUB(1);
                    $filter['knockout'] = $this->io->collectUB(1);
                    $filter['compositeSource'] = $this->io->collectUB(1);
                    $filter['onTop'] = $this->io->collectUB(1);
                    $filter['passes'] = $this->io->collectUB(4);
                    break;
                case 4: // GradientGlowFilter
                    $filter['numColors'] = $this->io->collectUI8();
                    $filter['gradientColors'] = array();
                    for ($i = 0; $i < $filter['numColors']; $i++) {
                        $filter['gradientColors'][] = $this->collectRGBA();
                    }
                    $filter['gradientRatio'] = array();
                    for ($i = 0; $i < $filter['numColors']; $i++) {
                        $filter['gradientRatio'][] = $this->io->collectUI8();
                    }
                    $filter['blurX'] = $this->io->collectFixed();
                    $filter['blurY'] = $this->io->collectFixed();
                    $filter['angle'] = $this->io->collectFixed();
                    $filter['distance'] = $this->io->collectFixed();
                    $filter['strength'] = $this->io->collectFixed8();
                    $filter['innerShadow'] = $this->io->collectUB(1);
                    $filter['knockout'] = $this->io->collectUB(1);
                    $filter['compositeSource'] = $this->io->collectUB(1);
                    $filter['onTop'] = $this->io->collectUB(1);
                    $filter['passes'] = $this->io->collectUB(4);
                    break;
                case 5: // ConvolutionFilter
                    $filter['matrixX'] = $this->io->collectUI8();
                    $filter['matrixY'] = $this->io->collectUI8();
                    $filter['divisor'] = $this->collectFloat();
                    $filter['bias'] = $this->collectFloat();
                    $filter['matrix'] = array();
                    for ($i = 0; $i < $filter['matrixX'] * $filter['matrixY']; $i++) {
                        $filter['matrix'][] = $this->collectFloat();
                    }
                    $filter['defaultColor'] = $this->collectRGBA();
                    $this->io->collectUB(6);
                    $filter['clamp'] = $this->io->collectUB(1);
                    $filter['preservedAlpha'] = $this->io->collectUB(1);
                    break;
                case 6: // ColorMatrixFilter
                    $matrix = array();
                    for ($i = 0; $i < 20; $i++) {
                        $matrix[$i] = $this->io->collectFloat();
                    }
                    $filter['matrix'] = $matrix;
                    break;
                case 7: // GradientBevelFilter
                    $filter['numColors'] = $this->io->collectUI8();
                    $filter['gradientColors'] = array();
                    for ($i = 0; $i < $filter['numColors']; $i++) {
                        $filter['gradientColors'][] = $this->collectRGBA();
                    }
                    $filter['gradientRatio'] = array();
                    for ($i = 0; $i < $filter['numColors']; $i++) {
                        $filter['gradientRatio'][] = $this->io->collectUI8();
                    }
                    $filter['blurX'] = $this->io->collectFixed();
                    $filter['blurY'] = $this->io->collectFixed();
                    $filter['angle'] = $this->io->collectFixed();
                    $filter['distance'] = $this->io->collectFixed();
                    $filter['strength'] = $this->io->collectFixed8();
                    $filter['innerShadow'] = $this->io->collectUB(1);
                    $filter['knockout'] = $this->io->collectUB(1);
                    $filter['compositeSource'] = $this->io->collectUB(1);
                    $filter['onTop'] = $this->io->collectUB(1);
                    $filter['passes'] = $this->io->collectUB(4);
                    break;
                default:
                    throw new Exception(sprintf('Internal error: filterId=%d', $filter['filterId']));
            }
            $filterList[] = $filter;
        }
        return $filterList;
    }

    public function collectSoundInfo() {
        $soundInfo = array();

        $this->io->collectUB(2); // Reserved
        $soundInfo['syncStop'] = $this->io->collectUB(1);
        $soundInfo['syncNoMultiple'] = $this->io->collectUB(1);
        $soundInfo['hasEnvelope'] = $this->io->collectUB(1);
        $soundInfo['hasLoops'] = $this->io->collectUB(1);
        $soundInfo['hasOutPoint'] = $this->io->collectUB(1);
        $soundInfo['hasInPoint'] = $this->io->collectUB(1);

        if ($soundInfo['hasInPoint'] != 0) {
            $soundInfo['inPoint'] = $this->io->collectUI32();
        }
        if ($soundInfo['hasOutPoint'] != 0) {
            $soundInfo['outPoint'] = $this->io->collectUI32();
        }
        if ($soundInfo['hasLoops'] != 0) {
            $soundInfo['loopCount'] = $this->io->collectUI16();
        }
        if ($soundInfo['hasEnvelope'] != 0) {
            $soundInfo['envelopeRecords'] = array();
            $envPoints = $this->io->collectUI8();
            for ($i = 0; $i < $envPoints; $i++) {
                $soundEnvelope = array();
                $soundEnvelope['pos44'] = $this->io->collectUI32();
                $soundEnvelope['leftLevel'] = $this->io->collectUI16();
                $soundEnvelope['rightLevel'] = $this->io->collectUI16();
                $soundInfo['envelopeRecords'][] = $soundEnvelope;
            }
        }
        return $soundInfo;
    }

    public function collectButtonRecords($version) {
        $buttonRecords = array();
        for (;;) {
            $buttonRecord = array();

            $reserved = $this->io->collectUB(2);
            $buttonRecord['buttonHasBlendMode'] = $this->io->collectUB(1);
            $buttonRecord['buttonHasFilterList'] = $this->io->collectUB(1);
            $buttonRecord['buttonStateHitTest'] = $this->io->collectUB(1);
            $buttonRecord['buttonStateDown'] = $this->io->collectUB(1);
            $buttonRecord['buttonStateOver'] = $this->io->collectUB(1);
            $buttonRecord['buttonStateUp'] = $this->io->collectUB(1);

            if ($reserved == 0 &&
                $buttonRecord['buttonHasBlendMode'] == 0 &&
                $buttonRecord['buttonHasFilterList'] == 0 &&
                $buttonRecord['buttonStateHitTest'] == 0 &&
                $buttonRecord['buttonStateDown'] == 0 &&
                $buttonRecord['buttonStateOver'] == 0 &&
                $buttonRecord['buttonStateUp'] == 0) {
                break;
            }

            $buttonRecord['characterId'] = $this->io->collectUI16();
            $buttonRecord['placeDepth'] = $this->io->collectUI16();
            $buttonRecord['placeMatrix'] = $this->collectMatrix();
            if ($version == 2) {
                $buttonRecord['colorTransform'] = $this->collectColorTransform(true);
            }
            if ($version == 2 && $buttonRecord['buttonHasFilterList'] != 0) {
                $buttonRecord['filterList'] = $this->collectFilterList();
            }
            if ($version == 2 && $buttonRecord['buttonHasBlendMode'] != 0) {
                $buttonRecord['blendMode'] = $this->io->collectUI8();
            }
            $buttonRecords[] = $buttonRecord;
        }
        return $buttonRecords;
    }

    public function collectButtonCondActions($bytePosEnd) {
        $buttonCondActions = array();
        for (;;) {
            $buttonCondAction = array();
            $here = $this->io->bytePos;
            $condActionSize = $this->io->collectUI16();

            $buttonCondAction['condIdleToOverDown'] = $this->io->collectUB(1);
            $buttonCondAction['condOutDownToIdle'] = $this->io->collectUB(1);
            $buttonCondAction['condOutDownToOverDown'] = $this->io->collectUB(1);
            $buttonCondAction['condOverDownToOutDown'] = $this->io->collectUB(1);
            $buttonCondAction['condOverDownToOverUp'] = $this->io->collectUB(1);
            $buttonCondAction['condOverUpToOverDown'] = $this->io->collectUB(1);
            $buttonCondAction['condOverUpToIdle'] = $this->io->collectUB(1);
            $buttonCondAction['condIdleToOverUp'] = $this->io->collectUB(1);

            $buttonCondAction['condKeyPress'] = $this->io->collectUB(7);
            $buttonCondAction['condOverDownToIdle'] = $this->io->collectUB(1);

            $buttonCondAction['actions'] = $this->collectActionRecords($condActionSize == 0 ? $bytePosEnd : $here + $condActionSize);

            $buttonCondActions[] = $buttonCondAction;
            if ($condActionSize == 0) {
                break;
            }
        }
        return $buttonCondActions;
    }

    public function collectClipActions($swfVersion) {
        $clipActions = array();
        $this->io->collectUI16(); // Reserved, must be 0
        $clipActions['allEventFlags'] = $this->collectClipEventFlags($swfVersion);
        $clipActions['clipActionRecords'] = array();
        for (;;) {
            // Collect clipActionEndFlag, if zero then break, if not zero then push back
            if ($swfVersion <= 5) {
                if (($endFlag = $this->io->collectUI16()) == 0) {
                    break;
                }
                $this->io->bytePos -= 2;
            } else {
                if (($endFlag = $this->io->collectUI32()) == 0) {
                    break;
                }
                $this->io->bytePos -= 4;
            }
            $clipActions['clipActionRecords'][] = $this->collectClipActionRecord($swfVersion);
        }
        return $clipActions;
    }

    public function collectClipActionRecord($swfVersion) {
        $clipActionRecord = array();
        $clipActionRecord['eventFlags'] = $this->collectClipEventFlags($swfVersion);
        $actionRecordSize = $this->io->collectUI32();
        $here = $this->io->bytePos;
        if (isset($clipActionRecord['eventFlags']['clipEventKeyPress']) && $clipActionRecord['eventFlags']['clipEventKeyPress'] == 1) {
            $clipActionRecord['keyCode'] = $this->io->collectUI8();
        }
        $clipActionRecord['actions'] = $this->collectActionRecords($here + $actionRecordSize);
        return $clipActionRecord;
    }

    public function collectClipEventFlags($swfVersion) {
        $ret = array();
        $ret['clipEventKeyUp'] = $this->io->collectUB(1);
        $ret['clipEventKeyDown'] = $this->io->collectUB(1);
        $ret['clipEventMouseUp'] = $this->io->collectUB(1);
        $ret['clipEventMouseDown'] = $this->io->collectUB(1);
        $ret['clipEventMouseMove'] = $this->io->collectUB(1);
        $ret['clipEventUnload'] = $this->io->collectUB(1);
        $ret['clipEventEnterFrame'] = $this->io->collectUB(1);
        $ret['clipEventLoad'] = $this->io->collectUB(1);

        $ret['clipEventDragOver'] = $this->io->collectUB(1);
        $ret['clipEventRollOut'] = $this->io->collectUB(1);
        $ret['clipEventRollOver'] = $this->io->collectUB(1);
        $ret['clipEventReleaseOutside'] = $this->io->collectUB(1);
        $ret['clipEventRelease'] = $this->io->collectUB(1);
        $ret['clipEventPress'] = $this->io->collectUB(1);
        $ret['clipEventInitialize'] = $this->io->collectUB(1);
        $ret['clipEventData'] = $this->io->collectUB(1);

        if ($swfVersion >= 6) {
            $this->io->collectUB(5); // Reserved
            $ret['clipEventConstruct'] = $this->io->collectUB(1);
            $ret['clipEventKeyPress'] = $this->io->collectUB(1);
            $ret['clipEventDragOut'] = $this->io->collectUB(1);
            $this->io->collectUB(8); // Reserved
        }
        return $ret;
    }

    public function collectGradientRecords($numGradientRecords, $shapeVersion) {
        $gradientRecords = array();
        for ($i = 0; $i < $numGradientRecords; $i++) {
            $gradientRecord = array();
            $gradientRecord['ratio'] = $this->io->collectUI8();
            if ($shapeVersion == 1 || $shapeVersion == 2) {
                $gradientRecord['color'] = $this->collectRGB();
            } else if ($shapeVersion == 3 || $shapeVersion == 4) { //XXX shapeVersion 4 not in spec
                $gradientRecord['color'] = $this->collectRGBA();
            } else {
                throw new Exception(sprintf('Internal error: shapeVersion=%d', $shapeVersion));
            }
            $gradientRecords[] = $gradientRecord;
        }
        return $gradientRecords;
    }

    public function collectTextRecords($glyphBits, $advanceBits, $textVersion) {
        $textRecords = array();
        // Collect text records
        for (;;) {
            $textRecord = array();
            $textRecord['textRecordType'] = $this->io->collectUB(1);
            $reserved = $this->io->collectUB(3); // Reserved, must be 0
            $textRecord['styleFlagsHasFont'] = $this->io->collectUB(1);
            $textRecord['styleFlagsHasColor'] = $this->io->collectUB(1);
            $textRecord['styleFlagsHasYOffset'] = $this->io->collectUB(1);
            $textRecord['styleFlagsHasXOffset'] = $this->io->collectUB(1);

            if ($textRecord['textRecordType'] == 0 &&
                $textRecord['styleFlagsHasFont'] == 0 && $textRecord['styleFlagsHasColor'] == 0 &&
                $textRecord['styleFlagsHasYOffset'] == 0 && $textRecord['styleFlagsHasXOffset'] == 0) {
                break;
            }

            if ($textRecord['styleFlagsHasFont'] != 0) {
                $textRecord['fontId'] = $this->io->collectUI16();
            }
            if ($textRecord['styleFlagsHasColor'] != 0) {
                $textRecord['textColor'] = $textVersion == 1 ? $this->collectRGB() : $this->collectRGBA();
            }
            if ($textRecord['styleFlagsHasXOffset'] != 0) {
                $textRecord['xOffset'] = $this->io->collectSI16();
            }
            if ($textRecord['styleFlagsHasYOffset'] != 0) {
                $textRecord['yOffset'] = $this->io->collectSI16();
            }
            if ($textRecord['styleFlagsHasFont'] != 0) {
                $textRecord['textHeight'] = $this->io->collectUI16();
            }
            $textRecord['glyphEntries'] = array();
            $glyphCount = $this->io->collectUI8();
            for ($i = 0; $i < $glyphCount; $i++) {
                $glyphEntry = array();
                $glyphEntry['glyphIndex'] = $this->io->collectUB($glyphBits);
                $glyphEntry['glyphAdvance'] = $this->io->collectSB($advanceBits);
                $textRecord['glyphEntries'][] = $glyphEntry;
            }
            $textRecords[] = $textRecord;
            $this->io->byteAlign();
        }
        return $textRecords;
    }

    public function collectFillStyleArray($shapeVersion) {
        $fillStyleCount = $this->io->collectUI8();
        if ($shapeVersion == 2 || $shapeVersion == 3 || $shapeVersion == 4) { //XXX shapeversion 4 not in spec
            if ($fillStyleCount == 0xff) {
                $fillStyleCount = $this->io->collectUI16(); // Extended
            }
        }
        $fillStyleArray = array();
        for ($i = 0; $i < $fillStyleCount; $i++) {
            $fillStyleArray[] = $this->collectFillStyle($shapeVersion);
        }
        return $fillStyleArray;
    }

    public function collectLineStyleArray($shapeVersion) {
        $lineStyleArray = array();
        $lineStyleCount = $this->io->collectUI8();
        if ($lineStyleCount == 0xff) {
            $lineStyleCount = $this->io->collectUI16(); // Extended
        }
        if ($shapeVersion == 1 || $shapeVersion == 2 || $shapeVersion == 3) {
            for ($i = 0; $i < $lineStyleCount; $i++) {
                $lineStyle = array();
                $lineStyle['width'] = $this->io->collectUI16();
                $lineStyle['color'] = $shapeVersion == 1 || $shapeVersion == 2 ? $this->collectRGB() : $this->collectRGBA();
                $lineStyleArray[] = $lineStyle;
            }
        } else if ($shapeVersion == 4) {
            for ($i = 0; $i < $lineStyleCount; $i++) {
                $lineStyle = array();
                $lineStyle['width'] = $this->io->collectUI16();

                $lineStyle['startCapStyle'] = $this->io->collectUB(2);
                $lineStyle['joinStyle'] = $this->io->collectUB(2);
                $lineStyle['hasFillFlag'] = $this->io->collectUB(1);
                $lineStyle['noHScaleFlag'] = $this->io->collectUB(1);
                $lineStyle['noVScaleFlag'] = $this->io->collectUB(1);
                $lineStyle['pixelHintingFlag'] = $this->io->collectUB(1);

                $this->io->collectUB(5); // Reserved, must be 0
                $lineStyle['noClose'] = $this->io->collectUB(1);
                $lineStyle['endCapStyle'] = $this->io->collectUB(2);

                if ($lineStyle['joinStyle'] == 2) {
                    $lineStyle['miterLimitFactor'] = $this->io->collectUI16();
                }
                if ($lineStyle['hasFillFlag'] == 0) {
                    $lineStyle['color'] = $this->collectRGBA();
                } else {
                    $lineStyle['fillType'] = $this->collectFillStyle($shapeVersion);
                }
                $lineStyleArray[] = $lineStyle;
            }
        } else {
            throw new Exception(sprintf('Internal error: shapeVersion=%d', $shapeVersion));
        }
        return $lineStyleArray;
    }

    public function collectFillStyle($shapeVersion) {
        $fillStyle = array();
        $fillStyle['type'] = $this->io->collectUI8();
        switch ($fillStyle['type']) {
            case 0x00: // Solid fill
                if ($shapeVersion == 1 || $shapeVersion == 2) {
                    $fillStyle['color'] = $this->collectRGB();
                } else if ($shapeVersion == 3 || $shapeVersion == 4) { //XXX shapeVersion 4 not in spec
                    $fillStyle['color'] = $this->collectRGBA();
                } else {
                    throw new Exception(sprintf('Internal error: shapeVersion=%d', $shapeVersion));
                }
                break;
            case 0x10: // Linear gradient fill
            case 0x12: // Radial gradient fill
            case 0x13: // Focal gradient fill
                $fillStyle['matrix'] = $this->collectMatrix();
                if ($fillStyle['type'] == 0x10 || $fillStyle['type'] == 0x12) {
                    $fillStyle['gradient'] = $this->collectGradient($shapeVersion);
                } else if ($fillStyle['type'] == 0x13) {
                    $fillStyle['focalGradient'] = $this->collectFocalGradient($shapeVersion);
                }
                break;
            case 0x40: // Repeating bitmap fill
            case 0x41: // Clipped bitmap fill
            case 0x42: // Non-smoothed repeating bitmap
            case 0x43: // Non-smoothed clipped bitmap
                $fillStyle['bitmapId'] = $this->io->collectUI16();
                $fillStyle['bitmapMatrix'] = $this->collectMatrix();
                break;
            default:
                throw new Exception(sprintf('Internal error: fillStyleType=%d', $fillStyle['type']));
        }
        $this->io->byteAlign();
        return $fillStyle;
    }

    public function collectZoneTable($bytePosEnd) {
        $zoneRecords = array();
        while ($this->io->bytePos < $bytePosEnd) {
            $zoneData = array();
            $numZoneData = $this->io->collectUI8();
            for ($i = 0; $i < $numZoneData; $i++) {
                $alignmentCoordinate = $this->io->collectFloat16();
                $range = $this->io->collectFloat16();
                $zoneData[] = array('alignmentCoordinate' => $alignmentCoordinate, 'range' => $range);
            }
            $this->io->collectUB(6); // Reserved;
            $zoneMaskY = $this->io->collectUB(1);
            $zoneMaskX = $this->io->collectUB(1);
            $zoneRecords[] = array('zoneData' => $zoneData, 'zoneMaskY' => $zoneMaskY, 'zoneMaskX' => $zoneMaskX);
        }
        return $zoneRecords;
    }
}

////////////////////////////////////////////////////////////////////////////////
// Basic IO
////////////////////////////////////////////////////////////////////////////////
class SWFio {
    public $b; // Byte array (file contents)
    public $bytePos; // Byte position
    public $bitPos; // Bit position

    public function __construct($b) {
        $this->b = $b;
        $this->bytePos = 0;
        $this->bitPos = 0;
    }

    public function doUncompress() {
        $this->b = substr($this->b, 0, 8) . gzuncompress(substr($this->b, 8));
    }

    public function byteAlign() {
        if ($this->bitPos != 0) {
            $this->bytePos++;
            $this->bitPos = 0;
        }
    }

    public function collectBytes($num) {
        $ret = substr($this->b, $this->bytePos, $num);
        $this->bytePos += $num;
        return $ret;
    }

    public function collectBits($num) {
        $value = 0;
        while ($num > 0) {
            $nextbits = ord($this->b[$this->bytePos]);
            $bitsFromHere = 8 - $this->bitPos;
            if ($bitsFromHere > $num) {
                $bitsFromHere = $num;
            }
            $value |= (($nextbits >> (8 - $this->bitPos - $bitsFromHere)) &
                    (0xff >> (8 - $bitsFromHere))) << ($num - $bitsFromHere);
            $num -= $bitsFromHere;
            $this->bitPos += $bitsFromHere;
            if ($this->bitPos >= 8) {
                $this->bitPos = 0;
                $this->bytePos++;
            }
        }
        return $value;
    }

    public function collectString() {
        $beg = $this->bytePos;
        while ($this->collectUI8() != 0) {
            ;
        }
        return substr($this->b, $beg, $this->bytePos - 1 - $beg);
    }

    // Bit values
    public function collectFB($num) { //XXX NOT SURE - NEEDS FIX
        $ret = $this->collectBits($num);
        if (($ret & (1 << ($num - 1))) == 0) {
            // Positive
            $hi = ($ret >> 16) & 0xffff;
            $lo = $ret & 0xffff;
            $ret = $hi + $lo / 65536.0;
        } else {
            // Negative
            $ret = (1 << $num) - $ret;
            $hi = ($ret >> 16) & 0xffff;
            $lo = $ret & 0xffff;
            $ret = -($hi + $lo / 65536.0);
        }
        // echo sprintf("collectFB, num is %d, will return [0x%04x]\n", $num, $ret);
        return $ret;
    }

    public function collectUB($num) {
        return $this->collectBits($num);
    }

    public function collectSB($num) {
        $val = $this->collectBits($num);
        if ($val >= (1 << ($num - 1))) { // If high bit is set
            $val -= 1 << $num; // Negate
        }
        return $val;
    }

    // Fixed point numbers
    public function collectFixed8() {
        $lo0 = $lo = $this->collectUI8();
        $hi0 = $hi = $this->collectUI8();
        if ($hi < 128) {
            $ret = $hi + $lo / 256.0;
        } else {
            $full = 65536 - (256 * $hi + $lo);
            $hi = $full >> 8;
            $lo = $full & 0xff;
            $ret = -($hi + $lo / 256.0);
        }
        // echo sprintf("collectFixed8 hi=[0x%X], lo=[0x%X], return [%s]\n", $hi0, $lo0, $ret);
        return $ret;
    }

    public function collectFixed() {
        $lo = $this->collectUI16();
        $hi = $this->collectUI16();
        $ret = $hi + $lo / 65536.0;
        // echo sprintf("collectFixed hi=[0x%X], lo=[0x%X], return [%s]\n", $hi, $lo, $ret);
        return $ret;
    }

    // Floating point numbers
    public function collectFloat16() {
        $w = $this->collectUI8() << 8;
        $w |= $this->collectUI8();
        $sign = ($w >> 15) & 0x0001; // 1 bit
        $exponent = ($w >> 10) & 0x001f; // 5 bits
        $mantissa = $w & 0x03ff; // 10 bits
        if ($exponent == 0x00) {
            return $sign == 0 ? '0.0' : '-0.0';
        } else if ($exponent == 0x3f) {
            return $mantissa != 0 ? NAN : ($sign == 0 ? INF : -INF);
        } else {
            $ret = $sign == 0 ? 1.0 : -1.0;
            if ($exponent > 16) {
                $ret *= 1 << ($exponent - 16);
            } else if ($exponent < 16) {
                $ret /= 1 << (16 - $exponent);
            }
            $ret *= (1.0 + $mantissa / 1024.0);
            // echo sprintf("float16: w=[0x%X], sign=[%d], exponent=[%d], mantissa=[%d], return=[%s]\n",
            // $w, $sign, $exponent, $mantissa, $ret);
            return $ret;
        }
    }

    public function collectFloat() {
        // We have to use bc... functions here
        bcscale(0); // 0 digits precision
        $w = '0';
        $mult = '1';
        for ($i = 0; $i < 4; $i++) {
            $w = bcadd($w, bcmul($mult, sprintf('%d', $this->collectUI8())));
            $mult = bcmul($mult, '256');
        }
        // Some constants
        $pow2_23 = bcpow('2', '23');

        $signAndExponent = intval(bcdiv($w, $pow2_23));
        $sign = ($signAndExponent >> 8) & 0x01; // 1 bit
        $exponent = ($signAndExponent) & 0xff; // 8 bits
        $mantissa = intval(bcmod($w, $pow2_23)); // 23 bits

        if ($exponent == 0) {
            return $sign == 0 ? '0.0' : '-0.0';
        } else if ($exponent == 255) {
            return $mantissa != 0 ? NAN : ($sign == 0 ? INF : -INF);
        } else {
            $ret = $sign == 0 ? 1.0 : -1.0;
            if ($exponent > 127) {
                $ret *= 1 << ($exponent - 127);
            } else if ($exponent < 127) {
                $ret /= 1 << (127 - $exponent);
            }
            $ret *= (1.0 + $mantissa / 8388608.0);
            // echo sprintf("float32: w=[0x%X], sign=[%d], exponent=[%d], mantissa=[%d], return=[%s]\n",
            // $w, $sign, $exponent, $mantissa, $ret);
            return $ret;
        }
    }

    public function collectDouble() {
        /* Another option: $float64 = unpack('d', pack('H*', '4455667700112233')); */

        // We have to use bc... functions here
        bcscale(0); // 0 digits precision
        $w = '0';
        // Bytes are arranged as: 45670123, where 7 is MSB and 0 is LSB
        $multipliers = array(bcpow('2', '32'), bcpow('2', '40'), bcpow('2', '48'), bcpow('2', '56'),
            bcpow('1',  '0'), bcpow('2',  '8'), bcpow('2', '16'), bcpow('2', '24'));
        for ($i = 0; $i < 8; $i++) {
            $w = bcadd($w, bcmul($multipliers[$i], sprintf('%d', $this->collectUI8())));
        }
        $pow2_52 = bcpow('2', '52');

        $signAndExponent = intval(bcdiv($w, $pow2_52));
        $sign = ($signAndExponent >> 11) & 0x01; // 1 bit
        $exponent = ($signAndExponent) & 0x7ff; // 11 bits
        $mantissa = bcmod($w, $pow2_52); // 52 bits

        if ($exponent == 0) {
            return $sign == 0 ? '0.0' : '-0.0';
        } else if ($exponent == 2047) {
            return bccomp($mantissa, '0') != 0 ? NAN : ($sign == 0 ? INF : -INF);
        } else {
            bcscale(20); // 20 digits precision
            $ret = $sign == 0 ? '1.0' : '-1.0';
            if ($exponent > 1023) {
                $ret = bcmul($ret, bcpow('2', sprintf('%d', ($exponent - 1023))));
            } else if ($exponent < 1023) {
                $ret = bcdiv($ret, bcpow('2', sprintf('%d', (1023 - $exponent))));
            }
            $ret = bcmul($ret, bcadd('1.0', bcdiv($mantissa, $pow2_52)));
            $ret = self::removeExtraZero($ret);
            // echo sprintf("float64: w=[0x%X], sign=[%d], exponent=[%d], mantissa=[%s], return=[%s]\n",
            // $w, $sign, $exponent, $mantissa, $ret);
            return $ret;
        }
    }

    // Integers
    public function collectUI8() {
        return ord($this->collectBytes(1));
    }

    public function collectSI16() {
        $ret = 0;
        $ret += ord($this->collectBytes(1));
        $ret += ord($this->collectBytes(1)) << 8;
        if ($ret >= 32768) {
            $ret -= 65536;
        }
        return $ret;
    }

    public function collectUI16() {
        $ret = 0;
        $ret += ord($this->collectBytes(1));
        $ret += ord($this->collectBytes(1)) << 8;
        return $ret;
    }

    public function collectSI32() {
        bcscale(0);
        $ret = '0';
        $ret = bcadd($ret, $this->collectUI8());
        $ret = bcadd($ret, bcmul($this->collectUI8(), '256'));
        $ret = bcadd($ret, bcmul($this->collectUI8(), '65536'));
        $ret = bcadd($ret, bcmul($this->collectUI8(), '16777216'));
        if (bccomp($ret, '2147483647') > 0) {
            $ret = bcsub($ret, '4294967296');
        }
        return $ret;
    }

    public function collectUI32() {
        bcscale(0);
        $ret = '0';
        $ret = bcadd($ret, $this->collectUI8());
        $ret = bcadd($ret, bcmul($this->collectUI8(), '256'));
        $ret = bcadd($ret, bcmul($this->collectUI8(), '65536'));
        $ret = bcadd($ret, bcmul($this->collectUI8(), '16777216'));
        return $ret;
    }

    public function collectEncodedU32() {
        bcscale(0);
        $ret = '0';
        $multiplier = '1';
        for (;;) {
            $b = $this->collectUI8();
            $ret = bcadd($ret, bcmul($b & 0x7f, $multiplier));
            $multiplier = bcmul($multiplier, '128');
            if (($b & 0x80) == 0) {
                return $ret;
            }
        }
    }

    private static function removeExtraZero($ret) {
        // Keep on removing trailing '00' by '0'
        while (($len = strlen($ret)) > 2 && $ret[$len - 1] == '0' && $ret[$len - 2] == '0') {
            $ret = substr($ret, 0, $len - 1);
        }
        return $ret;
    }

    // For debugging
    public function dumpPosition($len) {
        echo sprintf(" %04d.%1d:", $this->bytePos, $this->bitPos);
        for ($i = 0; $i < $len; $i++) {
            echo sprintf(" 0x%02x", ord($this->b[$this->bytePos + $i]));
        }
        echo sprintf("\n");
    }
}
