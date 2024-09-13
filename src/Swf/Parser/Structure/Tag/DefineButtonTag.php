<?php

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Action\ActionRecord;

final readonly class DefineButtonTag
{
    public function __construct(
        public int $buttonId,
        public array $characters,
        /**
         * @var list<ActionRecord>
         */
        public array $actions,
    ) {
    }
}
