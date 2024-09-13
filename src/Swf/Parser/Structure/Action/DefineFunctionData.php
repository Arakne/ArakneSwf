<?php

namespace Arakne\Swf\Parser\Structure\Action;

final readonly class DefineFunctionData
{
    public function __construct(
        public string $name,
        public array $parameters,
        public int $codeSize,
    ) {
    }
}
