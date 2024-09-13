<?php

namespace Arakne\Swf\Parser\Structure\Action;

final readonly class GotoFrame2Data
{
    public function __construct(
        public bool $sceneBiasFlag,
        public bool $playFlag,
        public ?int $sceneBias,
    ) {
    }
}
