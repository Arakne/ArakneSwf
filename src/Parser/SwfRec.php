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

use Arakne\Swf\Parser\Structure\Action\ActionRecord;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Filter\BevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ConvolutionFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\DropShadowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GlowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientBevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientGlowFilter;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Exception;

use function assert;
use function sprintf;

/**
 * Parse SWF structures
 */
readonly class SwfRec
{
    public function __construct(
        private SwfReader $io,
    ) {}

    ////////////////////////////////////////////////////////////////////////////////
    // More complex records
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * @return list<mixed>
     * @throws Exception
     */
    public function collectMorphFillStyleArray(): array
    {
        $morphFillStyleArray = [];

        $fillStyleCount = $this->io->readUI8();
        if ($fillStyleCount == 0xff) {
            $fillStyleCount = $this->io->readUI16(); // Extended
        }

        for ($i = 0; $i < $fillStyleCount; $i++) {
            $morphFillStyleArray[] = $this->collectMorphFillStyle();
        }

        return $morphFillStyleArray;
    }

    /**
     * @return array<string, mixed>
     */
    public function collectMorphFillStyle(): array
    {
        $morphFillStyle = []; // To return
        $morphFillStyle['fillStyleType'] = $this->io->readUI8();

        switch ($morphFillStyle['fillStyleType']) {
            case 0x00: // Solid fill
                $morphFillStyle['startColor'] = Color::readRgba($this->io);
                $morphFillStyle['endColor'] = Color::readRgba($this->io);
                break;
            case 0x10: // Linear gradient fill
            case 0x12: // Radial gradient fill
                $morphFillStyle['startGradientMatrix'] = Matrix::read($this->io);
                $morphFillStyle['endGradientMatrix'] = Matrix::read($this->io);
                $morphFillStyle['gradient'] = $this->collectMorphGradient();
                break;
            case 0x40: // Repeating bitmap
            case 0x41: // Clipped bitmap fill
            case 0x42: // Non-smoothed repeating bitmap
            case 0x43: // Non-smoothed clipped bitmap
                $morphFillStyle['bitmapId'] = $this->io->readUI16();
                $morphFillStyle['startBitmapMatrix'] = Matrix::read($this->io);
                $morphFillStyle['endBitmapMatrix'] = Matrix::read($this->io);
                break;
            default:
                throw new Exception(sprintf('Internal error: fillStyleType=%d', $morphFillStyle['fillStyleType']));
        }

        return $morphFillStyle;
    }

    /**
     * @return list<mixed>
     */
    public function collectMorphGradient(): array
    {
        $morphGradient = [];
        $numGradients = $this->io->readUI8();

        for ($i = 0; $i < $numGradients; $i++) {
            $morphGradient[] = $this->collectMorphGradientRecord();
        }

        return $morphGradient;
    }

    /**
     * @return array<string, mixed>
     */
    public function collectMorphGradientRecord(): array
    {
        return [
            'startRatio' => $this->io->readUI8(),
            'startColor' => Color::readRgba($this->io),
            'endRatio' => $this->io->readUI8(),
            'endColor' => Color::readRgba($this->io),
        ];
    }

    /**
     * @param int $version
     * @return list<mixed>
     */
    public function collectMorphLineStyleArray(int $version): array
    {
        $morphLineStyleArray = [];
        $lineStyleCount = $this->io->readUI8();

        if ($lineStyleCount == 0xff) {
            $lineStyleCount = $this->io->readUI16();
        }

        if ($version === 1) {
            for ($i = 0; $i < $lineStyleCount; $i++) {
                $morphLineStyleArray[] = $this->collectMorphLineStyle();
            }
        } elseif ($version === 2) {
            for ($i = 0; $i < $lineStyleCount; $i++) {
                $morphLineStyleArray[] = $this->collectMorphLineStyle2();
            }
        } else {
            throw new Exception(sprintf('Internal error: version=%d', $version));
        }

        return $morphLineStyleArray;
    }

    /**
     * @return array<string, mixed>
     */
    public function collectMorphLineStyle(): array
    {
        return [
            'startWidth' => $this->io->readUI16(),
            'endWidth' => $this->io->readUI16(),
            'startColor' => Color::readRgba($this->io),
            'endColor' => Color::readRgba($this->io),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function collectMorphLineStyle2(): array
    {
        $morphLineStyle2 = []; // To return
        $morphLineStyle2['startWidth'] = $this->io->readUI16();
        $morphLineStyle2['endWidth'] = $this->io->readUI16();

        $morphLineStyle2['startCapStyle'] = $this->io->readUB(2);
        $morphLineStyle2['joinStyle'] = $this->io->readUB(2);
        $morphLineStyle2['hasFillFlag'] = $this->io->readBool();
        $morphLineStyle2['noHScaleFlag'] = $this->io->readBool();
        $morphLineStyle2['noVScaleFlag'] = $this->io->readBool();
        $morphLineStyle2['pixelHintingFlag'] = $this->io->readBool();

        $this->io->skipBits(5); // Reserved
        $morphLineStyle2['noClose'] = $this->io->readBool();
        $morphLineStyle2['endCapStyle'] = $this->io->readUB(2);

        if ($morphLineStyle2['joinStyle'] === 2) {
            $morphLineStyle2['miterLimitFactor'] = $this->io->readUI16();
        }
        if ($morphLineStyle2['hasFillFlag'] === false) {
            $morphLineStyle2['startColor'] = Color::readRgba($this->io);
            $morphLineStyle2['endColor'] = Color::readRgba($this->io);
        }
        if ($morphLineStyle2['hasFillFlag'] === true) {
            $morphLineStyle2['fillType'] = $this->collectMorphFillStyle();
        }
        return $morphLineStyle2;
    }

    /**
     * @return list<DropShadowFilter|BlurFilter|GlowFilter|BevelFilter|GradientGlowFilter|ConvolutionFilter|ColorMatrixFilter|GradientBevelFilter>
     */
    public function collectFilterList(): array
    {
        $filterList = [];
        $numberOfFilters = $this->io->readUI8();

        for ($f = 0; $f < $numberOfFilters; $f++) {
            $filterId = $this->io->readUI8();

            switch ($filterId) {
                case 0: // DropShadowFilter
                    $filterList[] = new DropShadowFilter(
                        filterId: $filterId,
                        dropShadowColor: Color::readRgba($this->io),
                        blurX: $this->io->readFixed(),
                        blurY: $this->io->readFixed(),
                        angle: $this->io->readFixed(),
                        distance: $this->io->readFixed(),
                        strength: $this->io->readFixed8(),
                        innerShadow: $this->io->readBool(),
                        knockout: $this->io->readBool(),
                        compositeSource: $this->io->readBool(),
                        passes: $this->io->readUB(5),
                    );
                    break;
                case 1: // BlurFilter
                    $filterList[] = new BlurFilter(
                        filterId: $filterId,
                        blurX: $this->io->readFixed(),
                        blurY: $this->io->readFixed(),
                        passes: $this->io->readUB(5),
                        reserved: $this->io->readUB(3),
                    );
                    break;
                case 2: // GlowFilter
                    $filterList[] = new GlowFilter(
                        filterId: $filterId,
                        glowColor: Color::readRgba($this->io),
                        blurX: $this->io->readFixed(),
                        blurY: $this->io->readFixed(),
                        strength: $this->io->readFixed8(),
                        innerGlow: $this->io->readBool(),
                        knockout: $this->io->readBool(),
                        compositeSource: $this->io->readBool(),
                        passes: $this->io->readUB(5),
                    );
                    break;
                case 3: // BevelFilter
                    $filterList[] = new BevelFilter(
                        filterId: $filterId,
                        shadowColor: Color::readRgba($this->io),
                        highlightColor: Color::readRgba($this->io),
                        blurX: $this->io->readFixed(),
                        blurY: $this->io->readFixed(),
                        angle: $this->io->readFixed(),
                        distance: $this->io->readFixed(),
                        strength: $this->io->readFixed8(),
                        innerShadow: $this->io->readBool(),
                        knockout: $this->io->readBool(),
                        compositeSource: $this->io->readBool(),
                        onTop: $this->io->readBool(),
                        passes: $this->io->readUB(4),
                    );
                    break;
                case 4: // GradientGlowFilter
                    $numColors = $this->io->readUI8();
                    $gradientColors = [];
                    $gradientRatio = [];

                    for ($i = 0; $i < $numColors; $i++) {
                        $gradientColors[] = Color::readRgba($this->io);
                    }

                    for ($i = 0; $i < $numColors; $i++) {
                        $gradientRatio[] = $this->io->readUI8();
                    }

                    $filterList[] = new GradientGlowFilter(
                        filterId: $filterId,
                        numColors: $numColors,
                        gradientColors: $gradientColors,
                        gradientRatio: $gradientRatio,
                        blurX: $this->io->readFixed(),
                        blurY: $this->io->readFixed(),
                        angle: $this->io->readFixed(),
                        distance: $this->io->readFixed(),
                        strength: $this->io->readFixed8(),
                        innerShadow: $this->io->readBool(),
                        knockout: $this->io->readBool(),
                        compositeSource: $this->io->readBool(),
                        onTop: $this->io->readBool(),
                        passes: $this->io->readUB(4),
                    );
                    break;
                case 5: // ConvolutionFilter
                    $matrixX = $this->io->readUI8();
                    $matrixY = $this->io->readUI8();
                    $divisor = $this->io->readFloat();
                    $bias = $this->io->readFloat();
                    $matrix = [];

                    for ($i = 0; $i < $matrixX * $matrixY; $i++) {
                        $filter['matrix'][] = $this->io->readFloat();
                    }

                    $filterList[] = new ConvolutionFilter(
                        filterId: $filterId,
                        matrixX: $matrixX,
                        matrixY: $matrixY,
                        divisor: $divisor,
                        bias: $bias,
                        matrix: $matrix,
                        defaultColor: Color::readRgba($this->io),
                        reserved: $this->io->readUB(6),
                        clamp: $this->io->readBool(),
                        preserveAlpha: $this->io->readBool(),
                    );
                    break;
                case 6: // ColorMatrixFilter
                    $matrix = [];
                    for ($i = 0; $i < 20; $i++) {
                        $matrix[$i] = $this->io->readFloat();
                    }

                    $filterList[] = new ColorMatrixFilter(
                        filterId: $filterId,
                        matrix: $matrix,
                    );
                    break;
                case 7: // GradientBevelFilter
                    $numColors = $this->io->readUI8();
                    $gradientColors = [];
                    $gradientRatio = [];

                    for ($i = 0; $i < $numColors; $i++) {
                        $gradientColors[] = Color::readRgba($this->io);
                    }

                    for ($i = 0; $i < $numColors; $i++) {
                        $gradientRatio[] = $this->io->readUI8();
                    }

                    $filterList[] = new GradientBevelFilter(
                        filterId: $filterId,
                        numColors: $numColors,
                        gradientColors: $gradientColors,
                        gradientRatio: $gradientRatio,
                        blurX: $this->io->readFixed(),
                        blurY: $this->io->readFixed(),
                        angle: $this->io->readFixed(),
                        distance: $this->io->readFixed(),
                        strength: $this->io->readFixed8(),
                        innerShadow: $this->io->readBool(),
                        knockout: $this->io->readBool(),
                        compositeSource: $this->io->readBool(),
                        onTop: $this->io->readBool(),
                        passes: $this->io->readUB(4),
                    );
                    break;
                default:
                    throw new Exception(sprintf('Internal error: filterId=%d', $filterId));
            }
        }
        return $filterList;
    }

    /**
     * @return array<string, mixed>
     */
    public function collectSoundInfo(): array
    {
        $soundInfo = [];

        $this->io->skipBits(2); // Reserved
        $soundInfo['syncStop'] = $this->io->readBool();
        $soundInfo['syncNoMultiple'] = $this->io->readBool();
        $soundInfo['hasEnvelope'] = $this->io->readBool();
        $soundInfo['hasLoops'] = $this->io->readBool();
        $soundInfo['hasOutPoint'] = $this->io->readBool();
        $soundInfo['hasInPoint'] = $this->io->readBool();

        if ($soundInfo['hasInPoint'] != 0) {
            $soundInfo['inPoint'] = $this->io->readUI32();
        }
        if ($soundInfo['hasOutPoint'] != 0) {
            $soundInfo['outPoint'] = $this->io->readUI32();
        }
        if ($soundInfo['hasLoops'] != 0) {
            $soundInfo['loopCount'] = $this->io->readUI16();
        }
        if ($soundInfo['hasEnvelope'] != 0) {
            $soundInfo['envelopeRecords'] = [];
            $envPoints = $this->io->readUI8();
            for ($i = 0; $i < $envPoints; $i++) {
                $soundEnvelope = [];
                $soundEnvelope['pos44'] = $this->io->readUI32();
                $soundEnvelope['leftLevel'] = $this->io->readUI16();
                $soundEnvelope['rightLevel'] = $this->io->readUI16();
                $soundInfo['envelopeRecords'][] = $soundEnvelope;
            }
        }
        return $soundInfo;
    }

    /**
     * @param int $version
     * @return list<mixed>
     */
    public function collectButtonRecords(int $version): array
    {
        $buttonRecords = [];

        for (;;) {
            $buttonRecord = [];

            $this->io->skipBits(2);
            $buttonRecord['buttonHasBlendMode'] = $this->io->readBool();
            $buttonRecord['buttonHasFilterList'] = $this->io->readBool();
            $buttonRecord['buttonStateHitTest'] = $this->io->readBool();
            $buttonRecord['buttonStateDown'] = $this->io->readBool();
            $buttonRecord['buttonStateOver'] = $this->io->readBool();
            $buttonRecord['buttonStateUp'] = $this->io->readBool();

            if ($buttonRecord['buttonHasBlendMode'] == 0 &&
                $buttonRecord['buttonHasFilterList'] == 0 &&
                $buttonRecord['buttonStateHitTest'] == 0 &&
                $buttonRecord['buttonStateDown'] == 0 &&
                $buttonRecord['buttonStateOver'] == 0 &&
                $buttonRecord['buttonStateUp'] == 0) {
                break;
            }

            $buttonRecord['characterId'] = $this->io->readUI16();
            $buttonRecord['placeDepth'] = $this->io->readUI16();
            $buttonRecord['placeMatrix'] = Matrix::read($this->io);
            if ($version == 2) {
                $buttonRecord['colorTransform'] = ColorTransform::read($this->io, true);
            }
            if ($version == 2 && $buttonRecord['buttonHasFilterList'] != 0) {
                $buttonRecord['filterList'] = $this->collectFilterList();
            }
            if ($version == 2 && $buttonRecord['buttonHasBlendMode'] != 0) {
                $buttonRecord['blendMode'] = $this->io->readUI8();
            }
            $buttonRecords[] = $buttonRecord;
        }

        return $buttonRecords;
    }

    /**
     * @param int $bytePosEnd
     * @return list<mixed>
     */
    public function collectButtonCondActions(int $bytePosEnd): array
    {
        assert($bytePosEnd > 0); // @todo temporary before refactoring

        $buttonCondActions = [];
        for (;;) {
            $buttonCondAction = [];
            $here = $this->io->offset;
            $condActionSize = $this->io->readUI16();

            $buttonCondAction['condIdleToOverDown'] = $this->io->readBool();
            $buttonCondAction['condOutDownToIdle'] = $this->io->readBool();
            $buttonCondAction['condOutDownToOverDown'] = $this->io->readBool();
            $buttonCondAction['condOverDownToOutDown'] = $this->io->readBool();
            $buttonCondAction['condOverDownToOverUp'] = $this->io->readBool();
            $buttonCondAction['condOverUpToOverDown'] = $this->io->readBool();
            $buttonCondAction['condOverUpToIdle'] = $this->io->readBool();
            $buttonCondAction['condIdleToOverUp'] = $this->io->readBool();

            $buttonCondAction['condKeyPress'] = $this->io->readUB(7);
            $buttonCondAction['condOverDownToIdle'] = $this->io->readBool();

            $buttonCondAction['actions'] = ActionRecord::readCollection($this->io, $condActionSize == 0 ? $bytePosEnd : $here + $condActionSize);

            $buttonCondActions[] = $buttonCondAction;
            if ($condActionSize == 0) {
                break;
            }
        }
        return $buttonCondActions;
    }

    /**
     * @param int $swfVersion
     * @return array<string, mixed>
     */
    public function collectClipActions(int $swfVersion): array
    {
        $clipActions = [];
        $this->io->skipBytes(2); // Reserved, must be 0
        $clipActions['allEventFlags'] = $this->collectClipEventFlags($swfVersion);
        $clipActions['clipActionRecords'] = [];
        for (;;) {
            // Collect clipActionEndFlag, if zero then break, if not zero then push back
            // @todo "peek" method instead of push back, or simply let collectClipActionRecord return null
            if ($swfVersion <= 5) {
                if (($endFlag = $this->io->readUI16()) == 0) {
                    break;
                }
                // @phpstan-ignore-next-line
                $this->io->offset -= 2;
            } else {
                if (($endFlag = $this->io->readUI32()) == 0) {
                    break;
                }
                // @phpstan-ignore-next-line
                $this->io->offset -= 4;
            }
            $clipActions['clipActionRecords'][] = $this->collectClipActionRecord($swfVersion);
        }
        return $clipActions;
    }

    /**
     * @param int $swfVersion
     * @return array<string, mixed>
     */
    public function collectClipActionRecord(int $swfVersion): array
    {
        $clipActionRecord = [];
        $clipActionRecord['eventFlags'] = $this->collectClipEventFlags($swfVersion);
        $actionRecordSize = $this->io->readUI32();
        $here = $this->io->offset;
        if (isset($clipActionRecord['eventFlags']['clipEventKeyPress']) && $clipActionRecord['eventFlags']['clipEventKeyPress'] == 1) {
            $clipActionRecord['keyCode'] = $this->io->readUI8();
        }
        $clipActionRecord['actions'] = ActionRecord::readCollection($this->io, $here + $actionRecordSize);
        return $clipActionRecord;
    }

    /**
     * @param int $swfVersion
     * @return array<string, mixed>
     */
    public function collectClipEventFlags(int $swfVersion): array
    {
        // @todo read as UI16 / UI32 (depending on swfVersion), and return null if all flags are 0
        // So we do not need to perform a "push back" operation

        $ret = [];
        $ret['clipEventKeyUp'] = $this->io->readBool();
        $ret['clipEventKeyDown'] = $this->io->readBool();
        $ret['clipEventMouseUp'] = $this->io->readBool();
        $ret['clipEventMouseDown'] = $this->io->readBool();
        $ret['clipEventMouseMove'] = $this->io->readBool();
        $ret['clipEventUnload'] = $this->io->readBool();
        $ret['clipEventEnterFrame'] = $this->io->readBool();
        $ret['clipEventLoad'] = $this->io->readBool();

        $ret['clipEventDragOver'] = $this->io->readBool();
        $ret['clipEventRollOut'] = $this->io->readBool();
        $ret['clipEventRollOver'] = $this->io->readBool();
        $ret['clipEventReleaseOutside'] = $this->io->readBool();
        $ret['clipEventRelease'] = $this->io->readBool();
        $ret['clipEventPress'] = $this->io->readBool();
        $ret['clipEventInitialize'] = $this->io->readBool();
        $ret['clipEventData'] = $this->io->readBool();

        if ($swfVersion >= 6) {
            $this->io->skipBits(5); // Reserved
            $ret['clipEventConstruct'] = $this->io->readBool();
            $ret['clipEventKeyPress'] = $this->io->readBool();
            $ret['clipEventDragOut'] = $this->io->readBool();
            $this->io->skipBytes(1); // Reserved
        }
        return $ret;
    }

    /**
     * @param non-negative-int $glyphBits
     * @param non-negative-int $advanceBits
     * @param int $textVersion
     * @return list<mixed>
     */
    public function collectTextRecords(int $glyphBits, int $advanceBits, int $textVersion): array
    {
        $textRecords = [];
        // Collect text records
        for (;;) {
            $textRecord = [];
            $textRecord['textRecordType'] = $this->io->readBool();
            $this->io->skipBits(3); // Reserved, must be 0
            $textRecord['styleFlagsHasFont'] = $this->io->readBool();
            $textRecord['styleFlagsHasColor'] = $this->io->readBool();
            $textRecord['styleFlagsHasYOffset'] = $this->io->readBool();
            $textRecord['styleFlagsHasXOffset'] = $this->io->readBool();

            if ($textRecord['textRecordType'] == 0 &&
                $textRecord['styleFlagsHasFont'] == 0 && $textRecord['styleFlagsHasColor'] == 0 &&
                $textRecord['styleFlagsHasYOffset'] == 0 && $textRecord['styleFlagsHasXOffset'] == 0) {
                break;
            }

            if ($textRecord['styleFlagsHasFont'] != 0) {
                $textRecord['fontId'] = $this->io->readUI16();
            }
            if ($textRecord['styleFlagsHasColor'] != 0) {
                $textRecord['textColor'] = $textVersion == 1 ? Color::readRgb($this->io) : Color::readRgba($this->io);
            }
            if ($textRecord['styleFlagsHasXOffset'] != 0) {
                $textRecord['xOffset'] = $this->io->readSI16();
            }
            if ($textRecord['styleFlagsHasYOffset'] != 0) {
                $textRecord['yOffset'] = $this->io->readSI16();
            }
            if ($textRecord['styleFlagsHasFont'] != 0) {
                $textRecord['textHeight'] = $this->io->readUI16();
            }
            $textRecord['glyphEntries'] = [];
            $glyphCount = $this->io->readUI8();
            for ($i = 0; $i < $glyphCount; $i++) {
                $glyphEntry = [];
                $glyphEntry['glyphIndex'] = $this->io->readUB($glyphBits);
                $glyphEntry['glyphAdvance'] = $this->io->readSB($advanceBits);
                $textRecord['glyphEntries'][] = $glyphEntry;
            }
            $textRecords[] = $textRecord;
            $this->io->alignByte();
        }
        return $textRecords;
    }

    /**
     * @param int $bytePosEnd
     * @return list<mixed>
     */
    public function collectZoneTable(int $bytePosEnd): array
    {
        $zoneRecords = [];
        while ($this->io->offset < $bytePosEnd) {
            $zoneData = [];
            $numZoneData = $this->io->readUI8();
            for ($i = 0; $i < $numZoneData; $i++) {
                $alignmentCoordinate = $this->io->readFloat16();
                $range = $this->io->readFloat16();
                $zoneData[] = ['alignmentCoordinate' => $alignmentCoordinate, 'range' => $range];
            }
            $this->io->skipBits(6); // Reserved;
            $zoneMaskY = $this->io->readBool();
            $zoneMaskX = $this->io->readBool();
            $zoneRecords[] = ['zoneData' => $zoneData, 'zoneMaskY' => $zoneMaskY, 'zoneMaskX' => $zoneMaskX];
        }
        return $zoneRecords;
    }
}
