<?php

namespace Arakne\Swf\Parser\Structure;

/**
 * Structure for get tag offset and length before parsing
 */
final readonly class SwfTagPosition
{
    public function __construct(
        public int $type,
        public int $offset,
        public int $length,

        /**
         * The tag id is set only in case of a definition tag (e.g. DefineXXX)
         */
        public ?int $id = null,
    ) {
    }
}
