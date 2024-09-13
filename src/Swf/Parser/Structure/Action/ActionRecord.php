<?php

namespace Arakne\Swf\Parser\Structure\Action;

final readonly class ActionRecord
{
    public function __construct(
        public int $offset,
        public Opcode $opcode,
        public int $length,
        public mixed $data,
    ) {
    }
}
