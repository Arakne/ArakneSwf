<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class ProtectTag
{
    public function __construct(
        /**
         * Password is an MD5 hash of the password.
         */
        public ?string $password,
    ) {
    }
}
