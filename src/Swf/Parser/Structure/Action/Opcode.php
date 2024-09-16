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
 * Arakne-Swf: derived from SWF.php
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Parser\Structure\Action;

/**
 * Enum of all ActionScript 2 bytecodes
 * The value is the opcode of the bytecode
 */
enum Opcode: int
{
    case Null = 0x00;

    // SWF 3
    case ActionGotoFrame = 0x81;
    case ActionGetURL = 0x83;
    case ActionNextFrame = 0x04;
    case ActionPreviousFrame = 0x05;
    case ActionPlay = 0x06;
    case ActionStop = 0x07;
    case ActionToggleQuality = 0x08;
    case ActionStopSounds = 0x09;
    case ActionWaitForFrame = 0x8a;
    case ActionSetTarget = 0x8b;
    case ActionGoToLabel = 0x8c;

    // SWF 4
    case ActionPush = 0x96; // Stack operations
    case ActionPop = 0x17;
    case ActionAdd = 0x0a; // Arithmetic operators
    case ActionSubtract = 0x0b;
    case ActionMultiply = 0x0c;
    case ActionDivide = 0x0d;
    case ActionEquals = 0x0e; // Numerical comparison
    case ActionLess = 0x0f;
    case ActionAnd = 0x10; // Logical operators
    case ActionOr = 0x11;
    case ActionNot = 0x12;
    case ActionStringEquals = 0x13; // String manipulation
    case ActionStringLength = 0x14;
    case ActionStringAdd = 0x21;
    case ActionStringExtract = 0x15;
    case ActionStringLess = 0x29;
    case ActionMBStringLength = 0x31;
    case ActionMBStringExtract = 0x35;
    case ActionToInteger = 0x18; // Type conversion
    case ActionCharToAscii = 0x32;
    case ActionAsciiToChar = 0x33;
    case ActionMBCharToAscii = 0x36;
    case ActionMBAsciiToChar = 0x37;
    case ActionJump = 0x99; // Control flow
    case ActionIf = 0x9d;
    case ActionCall = 0x9e;
    case ActionGetVariable = 0x1c; // Variables
    case ActionSetVariable = 0x1d;
    case ActionGetURL2 = 0x9a; // Movie control
    case ActionGotoFrame2 = 0x9f;
    case ActionSetTarget2 = 0x20;
    case ActionGetProperty = 0x22;
    case ActionSetProperty = 0x23;
    case ActionCloneSprite = 0x24;
    case ActionRemoteSprite = 0x25;
    case ActionStartDrag = 0x27;
    case ActionEndDrag = 0x28;
    case ActionWaitForFrame2 = 0x8d;
    case ActionTrace = 0x26; // Utilities
    case ActionGetTime = 0x34;
    case ActionRandomNumber = 0x30;

    // SWF 5
    case ActionCallFunction = 0x3d; // ScriptObject actions
    case ActionCallMethod = 0x52;
    case ActionConstantPool = 0x88;
    case ActionDefineFunction = 0x9b;
    case ActionDefineLocal = 0x3c;
    case ActionDefineLocal2 = 0x41;
    case ActionDelete = 0x3a;
    case ActionDelete2 = 0x3b;
    case ActionEnumerate = 0x46;
    case ActionEquals2 = 0x49;
    case ActionGetMember = 0x4e;
    case ActionInitArray = 0x42;
    case ActionInitObject = 0x43;
    case ActionNewMethod = 0x53;
    case ActionNewObject = 0x40;
    case ActionSetMember = 0x4f;
    case ActionTargetPath = 0x45;
    case ActionWith = 0x94;
    case ActionToNumber = 0x4a; // Type actions
    case ActionToString = 0x4b;
    case ActionTypeOf = 0x44;
    case ActionAdd2 = 0x47; // Math actions
    case ActionLess2 = 0x48;
    case ActionModule = 0x3f;
    case ActionBitAnd = 0x60; // Stack operator actions
    case ActionBitLShift = 0x63;
    case ActionBitOr = 0x61;
    case ActionBitRShift = 0x64;
    case ActionBitURShift = 0x65;
    case ActionBitXor = 0x62;
    case ActionDecrement = 0x51;
    case ActionIncrement = 0x50;
    case ActionPushDuplicate = 0x4c;
    case ActionReturn = 0x3e;
    case ActionStackSwap = 0x4d;
    case ActionStoreRegister = 0x87;

    // SWF 6
    case DoInitAction = 0x59;
    case ActionInstanceOf = 0x54;
    case ActionEnumerate2 = 0x55;
    case ActionStrictEquals = 0x66;
    case ActionGreater = 0x67;
    case ActionStringGreater = 0x68;

    // SWF 7
    case ActionDefineFunction2 = 0x8e;
    case ActionExtends = 0x69;
    case ActionCastOp = 0x2b;
    case ActionImplementsOp = 0x2c;
    case ActionTry = 0x8f;
    case ActionThrow = 0x2a;

    // SWF 9
    case DoABC = 0x82;
    // SWF 10
}
