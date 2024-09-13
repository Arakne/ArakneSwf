<?php

namespace Arakne\Swf\Parser\Structure\Action;

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
        public array $parameters,
        public array $registers,
        public int $codeSize,
    ) {
    }
}
