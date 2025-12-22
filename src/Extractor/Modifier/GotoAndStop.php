<?php

namespace Arakne\Swf\Extractor\Modifier;

use Arakne\Swf\Extractor\Timeline\Timeline;
use Override;

use function is_string;

/**
 * Perform "gotoAndStop()" action on a character timeline
 *
 * @see Timeline::keepFrameByLabel() If a label is provided
 * @see Timeline::keepFrameByNumber() If a number is provided
 */
final class GotoAndStop extends AbstractCharacterModifier
{
    public function __construct(
        /**
         * The frame label or number to go to and stop at
         * If a label is provided but not found, the first frame is used
         */
        private readonly int|string $frame,
    ) {}

    #[Override]
    public function applyOnTimeline(Timeline $timeline): Timeline
    {
        return is_string($this->frame)
            ? $timeline->keepFrameByLabel($this->frame)
            : $timeline->keepFrameByNumber($this->frame)
        ;
    }
}
