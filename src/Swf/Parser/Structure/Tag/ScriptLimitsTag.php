<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class ScriptLimitsTag
{
    public function __construct(
        public int $maxRecursionDepth,
        public int $scriptTimeoutSeconds,
    ) {
    }
}
