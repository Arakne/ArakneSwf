<?php

namespace Arakne\Swf\Extractor\Shape;

final readonly class Shape
{
    public function __construct(
        public int $width,
        public int $height,
        public int $xOffset,
        public int $yOffset,

        /**
         * @var list<Path>
         */
        public array $paths,
    ) {}
}
