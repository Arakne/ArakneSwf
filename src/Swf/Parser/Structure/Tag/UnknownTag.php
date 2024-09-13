<?php

namespace Arakne\Swf\Parser\Structure\Tag;

/**
 * Unknown tag.
 * Can be used to represent a tag that is not yet implemented, an error, a custom tag,
 * or obfuscation mechanism.
 */
final readonly class UnknownTag
{
    public function __construct(
        public int $code,
        public string $data,
    ) {
    }
}
