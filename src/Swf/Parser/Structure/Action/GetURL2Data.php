<?php

namespace Arakne\Swf\Parser\Structure\Action;

final readonly class GetURL2Data
{
    public function __construct(
        public int $sendVarsMethod,
        public int $reserved,
        public bool $loadTargetFlag,
        public bool $loadVariablesFlag,
    ) {
    }
}
