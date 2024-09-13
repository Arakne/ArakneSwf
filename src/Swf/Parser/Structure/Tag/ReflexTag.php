<?php

namespace Arakne\Swf\Parser\Structure\Tag;

/**
 * This tag mark the swf as created by rfxswf.
 * It can be ignored.
 *
 * Note: this tag is not documented in the official SWF documentation.
 */
final readonly class ReflexTag
{
    public function __construct(
        /**
         * Should be "rfx".
         */
        public string $name,
    ) {
    }
}
