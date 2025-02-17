<?php

namespace Arakne\Swf\Extractor\Shape\FillType;

use Arakne\Swf\Parser\Structure\Record\ColorTransform;

/**
 *
 */
interface FillTypeInterface
{
    public function hash(): string;

    public function transformColors(ColorTransform $colorTransform): static;
}
