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
use Arakne\Swf\Parser\Structure\Action\GetURLData;
use Arakne\Swf\Parser\Structure\Action\GotoFrame2Data;
use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;
use Arakne\Swf\Parser\Structure\Action\WaitForFrameData;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\FillStyle;
use Arakne\Swf\Parser\Structure\Record\Filter\BevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\BlurFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\ConvolutionFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\DropShadowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GlowFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientBevelFilter;
use Arakne\Swf\Parser\Structure\Record\Filter\GradientGlowFilter;
use Arakne\Swf\Parser\Structure\Record\Gradient;
use Arakne\Swf\Parser\Structure\Record\GradientRecord;
use Arakne\Swf\Parser\Structure\Record\LineStyle;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Record\ShapeWithStyle;
use Arakne\Swf\Parser\Structure\Record\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\StyleChangeRecord;
use Exception;

use function sprintf;

/**
 * Parse SWF structures
 */
readonly class SwfRec
{
    public function __construct(
        private SwfReader $io,
    ) {}

    public function collectRGB(): Color
    {
        return new Color(
            $this->io->readUI8(),
            $this->io->readUI8(),
            $this->io->readUI8(),
        );
    }

    public function collectRGBA(): Color
    {
        return new Color(
            $this->io->readUI8(),
            $this->io->readUI8(),
            $this->io->readUI8(),
            $this->io->readUI8(),
        );
    }

    public function collectRect(): Rectangle
    {
        $nbits = $this->io->readUB(5);

        $ret = new Rectangle(
            $this->io->readSB($nbits),
            $this->io->readSB($nbits),
            $this->io->readSB($nbits),
            $this->io->readSB($nbits),
        );

        $this->io->alignByte();

        return $ret;
    }

    public function collectMatrix(): Matrix
    {
        $scaleX = 1.0;
        $scaleY = 1.0;
        $rotateSkew0 = 0.0;
        $rotateSkew1 = 0.0;
        $translateX = 0;
        $translateY = 0;

        if ($this->io->readBool()) {
            $nScaleBits = $this->io->readUB(5);
            $scaleX = $this->io->readFB($nScaleBits);
            $scaleY = $this->io->readFB($nScaleBits);
        }

        if ($this->io->readBool()) {
            $nRotateBits = $this->io->readUB(5);
            $rotateSkew0 = $this->io->readFB($nRotateBits);
            $rotateSkew1 = $this->io->readFB($nRotateBits);
        }

        if (($nTranslateBits = $this->io->readUB(5)) != 0) {
            $translateX = $this->io->readSB($nTranslateBits);
            $translateY = $this->io->readSB($nTranslateBits);
        }

        $this->io->alignByte();

        return new Matrix($scaleX, $scaleY, $rotateSkew0, $rotateSkew1, $translateX, $translateY);
    }

    public function collectColorTransform(bool $withAlpha): ColorTransform
    {
        $hasAddTerms = $this->io->readBool();
        $hasMultTerms = $this->io->readBool();
        $nbits = $this->io->readUB(4);

        $redMultTerm = 256;
        $greenMultTerm = 256;
        $blueMultTerm = 256;
        $alphaMultTerm = 256;
        $redAddTerm = 0;
        $greenAddTerm = 0;
        $blueAddTerm = 0;
        $alphaAddTerm = 0;

        if ($hasMultTerms != 0) {
            $redMultTerm = $this->io->readSB($nbits);
            $greenMultTerm = $this->io->readSB($nbits);
            $blueMultTerm = $this->io->readSB($nbits);
            if ($withAlpha) {
                $alphaMultTerm = $this->io->readSB($nbits);
            }
        }

        if ($hasAddTerms != 0) {
            $redAddTerm = $this->io->readSB($nbits);
            $greenAddTerm = $this->io->readSB($nbits);
            $blueAddTerm = $this->io->readSB($nbits);
            if ($withAlpha) {
                $alphaAddTerm = $this->io->readSB($nbits);
            }
        }

        $this->io->alignByte();

        return new ColorTransform(
            $redMultTerm,
            $greenMultTerm,
            $blueMultTerm,
            $alphaMultTerm,
            $redAddTerm,
            $greenAddTerm,
            $blueAddTerm,
            $alphaAddTerm,
        );
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
            if ($this->io->offset >= $bytePosEnd) {
                break;
            }

            $offset = $this->io->offset;
            $actionLength = 0;

            if (($actionCode = $this->io->readUI8()) === 0) {
                // echo sprintf("%6d: Code=0x%02x, breaking\n", $offset, $actionCode);
                $actions[] = new ActionRecord($offset, Opcode::Null, 0, null);
                continue; // break;
            }

            if ($actionCode >= 0x80) {
                $actionLength = $this->io->readUI16();
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

        if ($this->io->offset !== $bytePosEnd) {
            throw new Exception(sprintf('There are %d bytes left', $bytePosEnd - $this->io->offset));
        }

        return $actions;
    }

    /**
     * @param Opcode $opcode
     * @param non-negative-int $actionLength
     * @return mixed
     */
    public function collectActionData(Opcode $opcode, int $actionLength): mixed
    {
        return match ($opcode) {
            Opcode::ActionGotoFrame => $this->io->readUI16(),
            Opcode::ActionGetURL => new GetURLData(
                url: $this->io->readNullTerminatedString(),
                target: $this->io->readNullTerminatedString(),
            ),
            Opcode::ActionStoreRegister => $this->io->readUI8(),
            Opcode::ActionConstantPool => $this->collectConstantPool(),
            Opcode::ActionWaitForFrame => new WaitForFrameData(
                frame: $this->io->readUI16(),
                skipCount: $this->io->readUI8(),
            ),
            Opcode::ActionSetTarget => $this->io->readNullTerminatedString(),
            Opcode::ActionGoToLabel => $this->io->readNullTerminatedString(),
            Opcode::ActionWaitForFrame2 => $this->io->readUI8(),
            Opcode::ActionDefineFunction2 => $this->collectDefineFunction2(),
            Opcode::ActionWith => $this->io->readBytes($this->io->readUI16()),
            Opcode::ActionPush => $this->collectPush($actionLength),
            Opcode::ActionJump, Opcode::ActionIf => $this->io->readSI16(),
            Opcode::ActionGetURL2 => new GetURL2Data(
                sendVarsMethod: $this->io->readUB(2),
                reserved: $this->io->readUB(4),
                loadTargetFlag: $this->io->readBool(),
                loadVariablesFlag: $this->io->readBool(),
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
        $count = $this->io->readUI16();

        for ($i = 0; $i < $count; $i++) {
            $data[] = $this->io->readNullTerminatedString();
        }

        return $data;
    }

    private function collectDefineFunction2(): DefineFunction2Data
    {
        $functionName = $this->io->readNullTerminatedString();
        $numParams = $this->io->readUI16();
        $registerCount = $this->io->readUI8();
        $preloadParentFlag = $this->io->readBool();
        $preloadRootFlag = $this->io->readBool();
        $suppressSuperFlag = $this->io->readBool();
        $preloadSuperFlag = $this->io->readBool();
        $suppressArgumentsFlag = $this->io->readBool();
        $preloadArgumentsFlag = $this->io->readBool();
        $suppressThisFlag = $this->io->readBool();
        $preloadThisFlag = $this->io->readBool();
        $this->io->skipBits(7); // Reserved
        $preloadGlobalFlag = $this->io->readBool();

        $parameters = [];
        $registers = [];

        for ($i = 0; $i < $numParams; $i++) {
            $registers[] = $this->io->readUI8();
            $parameters[] = $this->io->readNullTerminatedString();
        }

        $codeSize = $this->io->readUI16();

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

    /**
     * @param non-negative-int $actionLength
     * @return list<Value>
     * @throws Exception
     */
    private function collectPush(int $actionLength): array
    {
        $actionData = [];
        $bytePosEnd = $this->io->offset + $actionLength;

        while ($this->io->offset < $bytePosEnd) {
            $typeId = $this->io->readUI8();
            $type = Type::tryFrom($typeId) ?? throw new Exception(sprintf("Internal error: typeId=%d", $typeId));

            $actionData[] = new Value(
                $type,
                match ($type) {
                    Type::String => $this->io->readNullTerminatedString(),
                    Type::Float => $this->io->readFloat(),
                    Type::Null => null,
                    Type::Undefined => null,
                    Type::Register => $this->io->readUI8(),
                    Type::Boolean => $this->io->readUI8() === 1,
                    Type::Double => $this->io->readDouble(),
                    Type::Integer => $this->io->readSI32(),
                    Type::Constant8 => $this->io->readUI8(),
                    Type::Constant16 => $this->io->readUI16(),
                }
            );
        }

        return $actionData;
    }

    private function collectDefineFunction(): DefineFunctionData
    {
        $name = $this->io->readNullTerminatedString();
        $params = [];
        $numParams = $this->io->readUI16();

        for ($i = 0; $i < $numParams; $i++) {
            $params[] = $this->io->readNullTerminatedString();
        }

        $codeSize = $this->io->readUI16();

        return new DefineFunctionData($name, $params, $codeSize);
    }

    private function collectGotoFrame2(): GotoFrame2Data
    {
        $this->io->skipBits(6); // Reserved

        $sceneBiasFlag = $this->io->readBool();
        $playFlag = $this->io->readBool();
        $sceneBias = $sceneBiasFlag ? $this->io->readUI16() : null;

        return new GotoFrame2Data($sceneBiasFlag, $playFlag, $sceneBias);
    }

    /**
     * @param int $shapeVersion
     * @return list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord>
     */
    public function collectShape(int $shapeVersion): array
    {
        $numFillBits = $this->io->readUB(4);
        $numLineBits = $this->io->readUB(4);

        return $this->collectShapeRecords($shapeVersion, $numFillBits, $numLineBits);
    }

    public function collectShapeWithStyle(int $shapeVersion): ShapeWithStyle
    {
        $fillStyles = $this->collectFillStyleArray($shapeVersion);
        $lineStyles = $this->collectLineStyleArray($shapeVersion);

        $numFillBits = $this->io->readUB(4);
        $numLineBits = $this->io->readUB(4);

        $shapeRecords = $this->collectShapeRecords($shapeVersion, $numFillBits, $numLineBits);

        return new ShapeWithStyle($fillStyles, $lineStyles, $shapeRecords);
    }

    /**
     * @param int $shapeVersion
     * @param non-negative-int $numFillBits
     * @param non-negative-int $numLineBits
     * @return list<StraightEdgeRecord|CurvedEdgeRecord|StyleChangeRecord|EndShapeRecord>
     * @throws Exception
     */
    public function collectShapeRecords(int $shapeVersion, int $numFillBits, int $numLineBits): array
    {
        $shapeRecords = [];

        for (;;) {
            $typeFlag = $this->io->readBool();
            if ($typeFlag === false) {
                $stateNewStyles = $this->io->readBool();
                $stateLineStyle = $this->io->readBool();
                $stateFillStyle1 = $this->io->readBool();
                $stateFillStyle0 = $this->io->readBool();
                $stateMoveTo = $this->io->readBool();
                if (!$stateNewStyles && !$stateLineStyle && !$stateFillStyle1 && !$stateFillStyle0 && !$stateMoveTo) {
                    // EndShapeRecord
                    $shapeRecords[] = new EndShapeRecord();
                    break;
                } else {
                    // StyleChangeRecord
                    if ($stateMoveTo) {
                        $moveBits = $this->io->readUB(5);
                        $moveDeltaX = $this->io->readSB($moveBits);
                        $moveDeltaY = $this->io->readSB($moveBits);
                    } else {
                        $moveDeltaX = 0;
                        $moveDeltaY = 0;
                    }

                    if ($stateFillStyle0) {
                        $fillStyle0 = $this->io->readUB($numFillBits);
                    } else {
                        $fillStyle0 = 0;
                    }

                    if ($stateFillStyle1) {
                        $fillStyle1 = $this->io->readUB($numFillBits);
                    } else {
                        $fillStyle1 = 0;
                    }

                    if ($stateLineStyle) {
                        $lineStyle = $this->io->readUB($numLineBits);
                    } else {
                        $lineStyle = 0;
                    }

                    if ($stateNewStyles && ($shapeVersion == 2 || $shapeVersion == 3 || $shapeVersion == 4)) { // XXX shapeVersion 4 not in spec
                        $this->io->alignByte();
                        $newFillStyles = $this->collectFillStyleArray($shapeVersion);
                        $newLineStyles = $this->collectLineStyleArray($shapeVersion);
                        $numFillBits = $this->io->readUB(4);
                        $numLineBits = $this->io->readUB(4);
                    } else {
                        $newFillStyles = [];
                        $newLineStyles = [];
                    }

                    $shapeRecords[] = new StyleChangeRecord(
                        $stateNewStyles,
                        $stateLineStyle,
                        $stateFillStyle0,
                        $stateFillStyle1,
                        $stateMoveTo,
                        $moveDeltaX,
                        $moveDeltaY,
                        $fillStyle0,
                        $fillStyle1,
                        $lineStyle,
                        $newFillStyles,
                        $newLineStyles,
                    );
                }
            } else {
                $straightFlag = $this->io->readBool();
                $numBits = $this->io->readUB(4);

                if ($straightFlag) {
                    // StraightEdgeRecord
                    $generalLineFlag = $this->io->readBool();
                    $vertLineFlag = !$generalLineFlag && $this->io->readBool();
                    $deltaX = $generalLineFlag || !$vertLineFlag ? $this->io->readSB($numBits + 2) : 0;
                    $deltaY = $generalLineFlag || $vertLineFlag ? $this->io->readSB($numBits + 2) : 0;

                    $shapeRecords[] = new StraightEdgeRecord($generalLineFlag, $vertLineFlag, $deltaX, $deltaY);
                } else {
                    // CurvedEdgeRecord
                    $shapeRecords[] = new CurvedEdgeRecord(
                        $this->io->readSB($numBits + 2),
                        $this->io->readSB($numBits + 2),
                        $this->io->readSB($numBits + 2),
                        $this->io->readSB($numBits + 2),
                    );
                }
            }
        }
        $this->io->alignByte();
        return $shapeRecords;
    }

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
                $morphFillStyle['bitmapId'] = $this->io->readUI16();
                $morphFillStyle['startBitmapMatrix'] = $this->collectMatrix();
                $morphFillStyle['endBitmapMatrix'] = $this->collectMatrix();
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
            'startColor' => $this->collectRGBA(),
            'endRatio' => $this->io->readUI8(),
            'endColor' => $this->collectRGBA(),
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
            'startColor' => $this->collectRGBA(),
            'endColor' => $this->collectRGBA(),
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
            $morphLineStyle2['startColor'] = $this->collectRGBA();
            $morphLineStyle2['endColor'] = $this->collectRGBA();
        }
        if ($morphLineStyle2['hasFillFlag'] === true) {
            $morphLineStyle2['fillType'] = $this->collectMorphFillStyle();
        }
        return $morphLineStyle2;
    }

    public function collectGradient(int $shapeVersion): Gradient
    {
        return new Gradient(
            spreadMode: $this->io->readUB(2),
            interpolationMode: $this->io->readUB(2),
            records: $this->collectGradientRecords($this->io->readUB(4), $shapeVersion),
        );
    }

    // shapeVersion must be 4
    public function collectFocalGradient(int $shapeVersion): Gradient
    {
        return new Gradient(
            spreadMode: $this->io->readUB(2),
            interpolationMode: $this->io->readUB(2),
            records: $this->collectGradientRecords($this->io->readUB(4), $shapeVersion),
            focalPoint: $this->io->readFixed8(),
        );
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
                        dropShadowColor: $this->collectRGBA(),
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
                        glowColor: $this->collectRGBA(),
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
                        shadowColor: $this->collectRGBA(),
                        highlightColor: $this->collectRGBA(),
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
                        $gradientColors[] = $this->collectRGBA();
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
                        defaultColor: $this->collectRGBA(),
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
                        $gradientColors[] = $this->collectRGBA();
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
            $buttonRecord['placeMatrix'] = $this->collectMatrix();
            if ($version == 2) {
                $buttonRecord['colorTransform'] = $this->collectColorTransform(true);
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

            $buttonCondAction['actions'] = $this->collectActionRecords($condActionSize == 0 ? $bytePosEnd : $here + $condActionSize);

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
        $clipActionRecord['actions'] = $this->collectActionRecords($here + $actionRecordSize);
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
     * @param int $numGradientRecords
     * @param int $shapeVersion
     * @return list<GradientRecord>
     * @throws Exception
     */
    public function collectGradientRecords(int $numGradientRecords, int $shapeVersion): array
    {
        $gradientRecords = [];

        for ($i = 0; $i < $numGradientRecords; $i++) {
            $gradientRecords[] = new GradientRecord(
                $this->io->readUI8(),
                match ($shapeVersion) {
                    1, 2 => $this->collectRGB(),
                    3, 4 => $this->collectRGBA(),
                    default => throw new Exception(sprintf('Internal error: shapeVersion=%d', $shapeVersion)),
                }
            );
        }

        return $gradientRecords;
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
                $textRecord['textColor'] = $textVersion == 1 ? $this->collectRGB() : $this->collectRGBA();
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
     * @param int $shapeVersion
     * @return list<FillStyle>
     */
    public function collectFillStyleArray(int $shapeVersion): array
    {
        $fillStyleCount = $this->io->readUI8();
        if ($shapeVersion == 2 || $shapeVersion == 3 || $shapeVersion == 4) { //XXX shapeversion 4 not in spec
            if ($fillStyleCount == 0xff) {
                $fillStyleCount = $this->io->readUI16(); // Extended
            }
        }
        $fillStyleArray = [];
        for ($i = 0; $i < $fillStyleCount; $i++) {
            $fillStyleArray[] = $this->collectFillStyle($shapeVersion);
        }
        return $fillStyleArray;
    }

    /**
     * @param int $shapeVersion
     * @return list<LineStyle>
     */
    public function collectLineStyleArray(int $shapeVersion): array
    {
        $lineStyleArray = [];
        $lineStyleCount = $this->io->readUI8();
        if ($lineStyleCount == 0xff) {
            $lineStyleCount = $this->io->readUI16(); // Extended
        }
        if ($shapeVersion == 1 || $shapeVersion == 2 || $shapeVersion == 3) {
            for ($i = 0; $i < $lineStyleCount; $i++) {
                $lineStyleArray[] = new LineStyle(
                    width: $this->io->readUI16(),
                    color: $shapeVersion == 1 || $shapeVersion == 2 ? $this->collectRGB() : $this->collectRGBA(),
                );
            }
        } elseif ($shapeVersion == 4) {
            for ($i = 0; $i < $lineStyleCount; $i++) {
                $width = $this->io->readUI16();

                $flags = $this->io->readUI8();
                $startCapStyle = ($flags >> 6) & 0b11; // 2bits
                $joinStyle = ($flags >> 4) & 0b11; // 4bits
                $hasFillFlag = ($flags & 0b1000) !== 0; // 5bits
                $noHScaleFlag = ($flags & 0b100) !== 0; // 6bits
                $noVScaleFlag = ($flags & 0b10) !== 0; // 7 bits
                $pixelHintingFlag = ($flags & 0b1) !== 0; // 8 bits

                $flags = $this->io->readUI8();
                // 5bits skipped
                $noClose = ($flags & 0b100) !== 0; // 6bits
                $endCapStyle = $flags & 0b11; // 8bits

                $miterLimitFactor = $joinStyle === 2 ? $this->io->readUI16() : null;

                if (!$hasFillFlag) {
                    $color = $this->collectRGBA();
                    $fillType = null;
                } else {
                    $fillType = $this->collectFillStyle($shapeVersion);
                    $color = null;
                }

                $lineStyleArray[] = new LineStyle(
                    width: $width,
                    color: $color,
                    startCapStyle: $startCapStyle,
                    joinStyle: $joinStyle,
                    hasFillFlag: $hasFillFlag,
                    noHScaleFlag: $noHScaleFlag,
                    noVScaleFlag: $noVScaleFlag,
                    pixelHintingFlag: $pixelHintingFlag,
                    noClose: $noClose,
                    endCapStyle: $endCapStyle,
                    miterLimitFactor: $miterLimitFactor,
                    fillType: $fillType,
                );
            }
        } else {
            throw new Exception(sprintf('Internal error: shapeVersion=%d', $shapeVersion));
        }
        return $lineStyleArray;
    }

    public function collectFillStyle(int $shapeVersion): FillStyle
    {
        $type = $this->io->readUI8();

        $style = match ($type) {
            FillStyle::SOLID => match ($shapeVersion) {
                1, 2 => new FillStyle($type, color: $this->collectRGB()),
                3, 4 => new FillStyle($type, color: $this->collectRGBA()), //XXX shapeVersion 4 not in spec
                default => throw new Exception(sprintf('Internal error: shapeVersion=%d', $shapeVersion)),
            },
            FillStyle::LINEAR_GRADIENT, FillStyle::RADIAL_GRADIENT => new FillStyle(
                $type,
                matrix: $this->collectMatrix(),
                gradient: $this->collectGradient($shapeVersion)
            ),
            FillStyle::FOCAL_GRADIENT => new FillStyle(
                $type,
                matrix: $this->collectMatrix(),
                focalGradient: $this->collectFocalGradient($shapeVersion),
            ),
            FillStyle::REPEATING_BITMAP, FillStyle::CLIPPED_BITMAP, FillStyle::NON_SMOOTHED_REPEATING_BITMAP, FillStyle::NON_SMOOTHED_CLIPPED_BITMAP => new FillStyle(
                $type,
                bitmapId: $this->io->readUI16(),
                bitmapMatrix: $this->collectMatrix(),
            ),
            default => throw new Exception(sprintf('Internal error: fillStyleType=%d', $type)),
        };

        $this->io->alignByte();
        return $style;
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
