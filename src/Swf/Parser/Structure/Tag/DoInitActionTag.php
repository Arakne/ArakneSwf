<?php

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Action\ActionRecord;

final readonly class DoInitActionTag
{
    public function __construct(
        public int $spriteId,
        /**
         * @var list<ActionRecord>
         */
        public array $actions,
    ) {
    }
}
