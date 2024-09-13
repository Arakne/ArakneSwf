<?php

namespace Arakne\Swf\Parser\Structure\Action;

final readonly class Value
{
    public function __construct(
        public Type $type,
        public int|float|string|bool|null $value,
    ) {
    }
}
