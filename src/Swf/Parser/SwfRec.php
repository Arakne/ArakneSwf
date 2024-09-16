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
use Arakne\Swf\Parser\Structure\Action\DefineFunction2Data;
use Arakne\Swf\Parser\Structure\Action\DefineFunctionData;
use Arakne\Swf\Parser\Structure\Action\GetURL2Data;
use Arakne\Swf\Parser\Structure\Action\GotoFrame2Data;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;
use Arakne\Swf\Parser\Structure\Action\WaitForFrameData;
use Arakne\Swf\Parser\Structure\Action\GetURLData;
use Arakne\Swf\Parser\Structure\Action\Opcode;
use Exception;

use function sprintf;

/**
 * Parse SWF structures
 */
readonly class SwfRec
{
    public function __construct(
        private SwfIO $io,
    ) {}

    public function collectRGB(): array
    {
        return [
            'red' => $this->io->collectUI8(),
            'green' => $this->io->collectUI8(),
            'blue' => $this->io->collectUI8(),
        ];
    }

    public function collectRGBA(): array
    {
        return [
            'red' => $this->io->collectUI8(),
            'green' => $this->io->collectUI8(),
            'blue' => $this->io->collectUI8(),
            'alpha' => $this->io->collectUI8(),
        ];
    }

    public function collectRect(): array
    {
        $nbits = $this->io->collectUB(5);

        $ret = [
            'xmin' => $this->io->collectSB($nbits),
            'xmax' => $this->io->collectSB($nbits),
            'ymin' => $this->io->collectSB($nbits),
            'ymax' => $this->io->collectSB($nbits),
        ];

        $this->io->byteAlign();

        return $ret;
    }

    public function collectMatrix(): array
    {
        $ret = [];

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

    public function collectColorTransform(bool $withAlpha): array
    {
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

    /**
     * @param int $bytePosEnd
     * @return list<ActionRecord>
     * @throws Exception
     */
    public function collectActionRecords(int $bytePosEnd): array
    {
        $actions =  [];

        for (;;) {
            if ($this->io->bytePos >= $bytePosEnd) {
                break;
            }

            $offset = $this->io->bytePos;
            $actionLength = 0;

            if (($actionCode = $this->io->collectUI8()) === 0) {
                // echo sprintf("%6d: Code=0x%02x, breaking\n", $offset, $actionCode);
                $actions[] = new ActionRecord($offset, Opcode::Null, 0, null);
                continue; // break;
            }

            if ($actionCode >= 0x80) {
                $actionLength = $this->io->collectUI16();
            }

            $opcode = Opcode::tryFrom($actionCode);

            if (!$opcode) {
                throw new Exception(sprintf("Internal error: actionCode=0x%02X, actionLength=%d", $actionCode, $actionLength));
            }

            /** @var mixed $actionData */
            $actionData = $actionLength > 0 ? $this->collectActionData($opcode, $actionLength) : null;

            // echo sprintf("%6d: Code=0x%02x, length=%d, name=%s\n", $offset, $actionCode, $actionLength, $actionName);
            $actions[] = new ActionRecord($offset, $opcode, $actionLength, $actionData);
        }

        if ($this->io->bytePos !== $bytePosEnd) {
            throw new Exception(sprintf('There are %d bytes left', $bytePosEnd - $this->io->bytePos));
        }

        return $actions;
    }

    public function collectActionData(Opcode $opcode, int $actionLength): mixed
    {
        return match ($opcode) {
            Opcode::ActionGotoFrame => $this->io->collectUI16(),
            Opcode::ActionGetURL => new GetURLData(
                url: $this->io->collectString(),
                target: $this->io->collectString(),
            ),
            Opcode::ActionStoreRegister => $this->io->collectUI8(),
            Opcode::ActionConstantPool => $this->collectConstantPool(),
            Opcode::ActionWaitForFrame => new WaitForFrameData(
                frame: $this->io->collectUI16(),
                skipCount: $this->io->collectUI8(),
            ),
            Opcode::ActionSetTarget => $this->io->collectString(),
            Opcode::ActionGoToLabel => $this->io->collectString(),
            Opcode::ActionWaitForFrame2 => $this->io->collectUI8(),
            Opcode::ActionDefineFunction2 => $this->collectDefineFunction2(),
            Opcode::ActionWith => $this->io->collectBytes($this->io->collectUI16()),
            Opcode::ActionPush => $this->collectPush($actionLength),
            Opcode::ActionJump, Opcode::ActionIf => $this->io->collectSI16(),
            Opcode::ActionGetURL2 => new GetURL2Data(
                sendVarsMethod: $this->io->collectUB(2),
                reserved: $this->io->collectUB(4),
                loadTargetFlag: $this->io->collectUB(1) === 1,
                loadVariablesFlag: $this->io->collectUB(1) === 1,
            ),
            Opcode::ActionDefineFunction => $this->collectDefineFunction(),
            Opcode::ActionGotoFrame2 => $this->collectGotoFrame2(),
            default => throw new Exception(sprintf("Internal error: opcode=%s, actionLength=%d", $opcode->name, $actionLength)),
        };
    }

    /**
     * @return list<string>
     */
    private function collectConstantPool(): array
    {
        $data = [];
        $count = $this->io->collectUI16();

        for ($i = 0; $i < $count; $i++) {
            $data[] = $this->io->collectString();
        }

        return $data;
    }

    private function collectDefineFunction2(): DefineFunction2Data
    {
        $functionName = $this->io->collectString();
        $numParams = $this->io->collectUI16();
        $registerCount = $this->io->collectUI8();
        $preloadParentFlag = $this->io->collectUB(1) === 1;
        $preloadRootFlag = $this->io->collectUB(1) === 1;
        $suppressSuperFlag = $this->io->collectUB(1) === 1;
        $preloadSuperFlag = $this->io->collectUB(1) === 1;
        $suppressArgumentsFlag = $this->io->collectUB(1) === 1;
        $preloadArgumentsFlag = $this->io->collectUB(1) === 1;
        $suppressThisFlag = $this->io->collectUB(1) === 1;
        $preloadThisFlag = $this->io->collectUB(1) === 1;
        $this->io->collectUB(7); // Reserved
        $preloadGlobalFlag = $this->io->collectUB(1) === 1;

        $parameters = [];
        $registers = [];

        for ($i = 0; $i < $numParams; $i++) {
            $registers[] = $this->io->collectUI8();
            $parameters[] = $this->io->collectString();
        }

        $codeSize = $this->io->collectUI16();

        return new DefineFunction2Data(
            $functionName,
            $registerCount,
            $preloadParentFlag,
            $preloadRootFlag,
            $suppressSuperFlag,
            $preloadSuperFlag,
            $suppressArgumentsFlag,
            $preloadArgumentsFlag,
            $suppressThisFlag,
            $preloadThisFlag,
            $preloadGlobalFlag,
            $parameters,
            $registers,
            $codeSize,
        );
    }

    private function collectPush(int $actionLength): array
    {
        $actionData = [];
        $bytePosEnd = $this->io->bytePos + $actionLength;

        while ($this->io->bytePos < $bytePosEnd) {
            $typeId = $this->io->collectUI8();
            $type = Type::tryFrom($typeId) ?? throw new Exception(sprintf("Internal error: typeId=%d", $typeId));

            $actionData[] = new Value(
                $type,
                match ($type) {
                    Type::String => $this->io->collectString(),
                    Type::Float => $this->io->collectFloat(),
                    Type::Null => null,
                    Type::Undefined => null,
                    Type::Register => $this->io->collectUI8(),
                    Type::Boolean => $this->io->collectUI8() === 1,
                    Type::Double => $this->io->collectDouble(),
                    Type::Integer => $this->io->collectSI32(),
                    Type::Constant8 => $this->io->collectUI8(),
                    Type::Constant16 => $this->io->collectUI16(),
                }
            );
        }

        return $actionData;
    }

    private function collectDefineFunction(): DefineFunctionData
    {
        $name = $this->io->collectString();
        $params = [];
        $numParams = $this->io->collectUI16();

        for ($i = 0; $i < $numParams; $i++) {
            $params = $this->io->collectString();
        }

        $codeSize = $this->io->collectUI16();

        return new DefineFunctionData($name, $params, $codeSize);
    }

    private function collectGotoFrame2(): GotoFrame2Data
    {
        $this->io->collectUB(6); // Reserved

        $sceneBiasFlag = $this->io->collectUB(1) === 1;
        $playFlag = $this->io->collectUB(1) === 1;
        $sceneBias = $sceneBiasFlag ? $this->io->collectUI16() : null;

        return new GotoFrame2Data($sceneBiasFlag, $playFlag, $sceneBias);
    }

    public function collectShape(int $shapeVersion): array
    {
        $numFillBits = $this->io->collectUB(4);
        $numLineBits = $this->io->collectUB(4);

        return $this->collectShapeRecords($shapeVersion, null, null, $numFillBits, $numLineBits);
    }

    public function collectShapeWithStyle(int $shapeVersion): array
    {
        $ret = [
            'fillStyles' => $this->collectFillStyleArray($shapeVersion),
            'lineStyles' => $this->collectLineStyleArray($shapeVersion),
        ];

        $numFillBits = $this->io->collectUB(4);
        $numLineBits = $this->io->collectUB(4);

        $ret['shapeRecords'] = $this->collectShapeRecords($shapeVersion, $ret['fillStyles'], $ret['lineStyles'], $numFillBits, $numLineBits);

        return $ret;
    }

    public function collectShapeRecords(int $shapeVersion, ?array $fillStyles, ?array $lineStyles, int $numFillBits, int $numLineBits): array
    {
        $shapeRecords = [];

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

    public function collectMorphFillStyleArray(): array
    {
        $morphFillStyleArray = [];

        $fillStyleCount = $this->io->collectUI8();
        if ($fillStyleCount == 0xff) {
            $fillStyleCount = $this->io->collectUI16(); // Extended
        }

        for ($i = 0; $i < $fillStyleCount; $i++) {
            $morphFillStyleArray[] = $this->collectMorphFillStyle();
        }

        return $morphFillStyleArray;
    }

    public function collectMorphFillStyle(): array
    {
        $morphFillStyle = []; // To return
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

    public function collectMorphGradient(): array
    {
        $morphGradient = [];
        $numGradients = $this->io->collectUI8();

        for ($i = 0; $i < $numGradients; $i++) {
            $morphGradient[] = $this->collectMorphGradientRecord();
        }

        return $morphGradient;
    }

    public function collectMorphGradientRecord(): array
    {
        return [
            'startRatio' => $this->io->collectUI8(),
            'startColor' => $this->collectRGBA(),
            'endRatio' => $this->io->collectUI8(),
            'endColor' => $this->collectRGBA(),
        ];
    }

    public function collectMorphLineStyleArray(int $version): array
    {
        $morphLineStyleArray = [];
        $lineStyleCount = $this->io->collectUI8();

        if ($lineStyleCount == 0xff) {
            $lineStyleCount = $this->io->collectUI16();
        }

        if ($version === 1) {
            for ($i = 0; $i < $lineStyleCount; $i++) {
                $morphLineStyleArray[] = $this->collectMorphLineStyle();
            }
        } else if ($version === 2) {
            for ($i = 0; $i < $lineStyleCount; $i++) {
                $morphLineStyleArray[] = $this->collectMorphLineStyle2();
            }
        } else {
            throw new Exception(sprintf('Internal error: version=%d', $version));
        }

        return $morphLineStyleArray;
    }

    public function collectMorphLineStyle(): array
    {
        return [
            'startWidth' => $this->io->collectUI16(),
            'endWidth' => $this->io->collectUI16(),
            'startColor' => $this->collectRGBA(),
            'endColor' => $this->collectRGBA(),
        ];
    }

    public function collectMorphLineStyle2(): array
    {
        $morphLineStyle2 = []; // To return
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

        if ($morphLineStyle2['joinStyle'] === 2) {
            $morphLineStyle2['miterLimitFactor'] = $this->io->collectUI16();
        }
        if ($morphLineStyle2['hasFillFlag'] === 0) {
            $morphLineStyle2['startColor'] = $this->collectRGBA();
            $morphLineStyle2['endColor'] = $this->collectRGBA();
        }
        if ($morphLineStyle2['hasFillFlag'] === 1) {
            $morphLineStyle2['fillType'] = $this->collectMorphFillStyle();
        }
        return $morphLineStyle2;
    }

    public function collectGradient(int $shapeVersion): array
    {
        $gradient = [];
        $gradient['spreadMode'] = $this->io->collectUB(2);
        $gradient['interpolationMode'] = $this->io->collectUB(2);
        $numGradientRecords = $this->io->collectUB(4);
        $gradient['gradientRecords'] = $this->collectGradientRecords($numGradientRecords, $shapeVersion);
        return $gradient;
    }

    // shapeVersion must be 4
    public function collectFocalGradient(int $shapeVersion): array
    {
        $focalGradient = array();
        $focalGradient['spreadMode'] = $this->io->collectUB(2);
        $focalGradient['interpolationMode'] = $this->io->collectUB(2);
        $numGradientRecords = $this->io->collectUB(4);
        $focalGradient['gradientRecords'] = $this->collectGradientRecords($numGradientRecords, $shapeVersion);
        $focalGradient['focalPoint'] = $this->io->collectFixed8();
        return $focalGradient;
    }

    public function collectFilterList(): array
    {
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

    public function collectSoundInfo(): array
    {
        $soundInfo = [];

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

    public function collectButtonRecords(int $version): array {
        $buttonRecords = [];

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

    public function collectButtonCondActions(int $bytePosEnd): array
    {
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

    public function collectClipActions(int $swfVersion): array
    {
        $clipActions = [];
        $this->io->collectUI16(); // Reserved, must be 0
        $clipActions['allEventFlags'] = $this->collectClipEventFlags($swfVersion);
        $clipActions['clipActionRecords'] = [];
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

    public function collectClipActionRecord(int $swfVersion): array
    {
        $clipActionRecord = [];
        $clipActionRecord['eventFlags'] = $this->collectClipEventFlags($swfVersion);
        $actionRecordSize = $this->io->collectUI32();
        $here = $this->io->bytePos;
        if (isset($clipActionRecord['eventFlags']['clipEventKeyPress']) && $clipActionRecord['eventFlags']['clipEventKeyPress'] == 1) {
            $clipActionRecord['keyCode'] = $this->io->collectUI8();
        }
        $clipActionRecord['actions'] = $this->collectActionRecords($here + $actionRecordSize);
        return $clipActionRecord;
    }

    public function collectClipEventFlags(int $swfVersion): array
    {
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

    public function collectGradientRecords(int $numGradientRecords, int $shapeVersion): array
    {
        $gradientRecords = [];
        for ($i = 0; $i < $numGradientRecords; $i++) {
            $gradientRecord = array();
            $gradientRecord['ratio'] = $this->io->collectUI8();
            if ($shapeVersion === 1 || $shapeVersion === 2) {
                $gradientRecord['color'] = $this->collectRGB();
            } else if ($shapeVersion === 3 || $shapeVersion === 4) { //XXX shapeVersion 4 not in spec
                $gradientRecord['color'] = $this->collectRGBA();
            } else {
                throw new Exception(sprintf('Internal error: shapeVersion=%d', $shapeVersion));
            }
            $gradientRecords[] = $gradientRecord;
        }
        return $gradientRecords;
    }

    public function collectTextRecords(int $glyphBits, int $advanceBits, int $textVersion): array
    {
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

    public function collectFillStyleArray(int $shapeVersion): array
    {
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

    public function collectLineStyleArray(int $shapeVersion): array
    {
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

    public function collectFillStyle(int $shapeVersion): array
    {
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

    public function collectZoneTable(int $bytePosEnd): array
    {
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
