<?php

namespace Arakne\Swf\Extractor\Shape\FillType;

/**
 *
 */
interface FillTypeInterface
{
    public function hash(): string;

    public function transformColors(array $colorTransform): static;
}
