<?php

namespace Arakne\Swf\Parser\Structure;

final readonly class SwfHeader
{
    public function __construct(
        /**
         * @var "FWS"|"CWS"
         */
        public string $signature,
        public int $version,
        public int $fileLength,
        public array $frameSize,
        public float $frameRate,
        public int $frameCount,
    ) {
    }
}
