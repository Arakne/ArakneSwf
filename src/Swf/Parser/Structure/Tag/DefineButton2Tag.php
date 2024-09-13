<?php

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Action\ActionRecord;

final readonly class DefineButton2Tag
{
    public function __construct(
        public int $buttonId,
        public bool $trackAsMenu,
        public int $actionOffset,
        public array $characters,
        public array $actions,
    ) {
    }
}
