<?php

namespace Arakne\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Action\ActionRecord;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * @implements IteratorAggregate<int, ActionRecord>
 */
final readonly class DoActionTag implements IteratorAggregate
{
    public function __construct(
        /**
         * @var list<ActionRecord>
         */
        public array $actions,
    ) {
    }

    #[Override]
    public function getIterator(): Traversable
    {
        yield from $this->actions;
    }
}
