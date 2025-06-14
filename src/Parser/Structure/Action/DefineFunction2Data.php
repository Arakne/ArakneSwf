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

namespace Arakne\Swf\Parser\Structure\Action;

use Arakne\Swf\Parser\SwfReader;

final readonly class DefineFunction2Data
{
    public function __construct(
        public string $name,
        public int $registerCount,
        public bool $preloadParentFlag,
        public bool $preloadRootFlag,
        public bool $suppressSuperFlag,
        public bool $preloadSuperFlag,
        public bool $suppressArgumentsFlag,
        public bool $preloadArgumentsFlag,
        public bool $suppressThisFlag,
        public bool $preloadThisFlag,
        public bool $preloadGlobalFlag,

        /** @var list<string> */
        public array $parameters,

        /** @var list<int> */
        public array $registers,
        public int $codeSize,
    ) {}

    public static function read(SwfReader $reader): self
    {
        $functionName = $reader->readNullTerminatedString();
        $numParams = $reader->readUI16();
        $registerCount = $reader->readUI8();
        $preloadParentFlag = $reader->readBool();
        $preloadRootFlag = $reader->readBool();
        $suppressSuperFlag = $reader->readBool();
        $preloadSuperFlag = $reader->readBool();
        $suppressArgumentsFlag = $reader->readBool();
        $preloadArgumentsFlag = $reader->readBool();
        $suppressThisFlag = $reader->readBool();
        $preloadThisFlag = $reader->readBool();
        $reader->skipBits(7); // Reserved
        $preloadGlobalFlag = $reader->readBool();

        $parameters = [];
        $registers = [];

        for ($i = 0; $i < $numParams; $i++) {
            $registers[] = $reader->readUI8();
            $parameters[] = $reader->readNullTerminatedString();
        }

        $codeSize = $reader->readUI16();

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
}
