<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineSceneAndFrameLabelDataTag
{
    public function __construct(
        public array $sceneOffsets,
        public array $sceneNames,
        public array $frameNumbers,
        public array $frameLabels,
    ) {
    }
}
