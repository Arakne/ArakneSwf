<?php

namespace Arakne\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Shape\Svg\SvgShapeDrawer;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;

final class ShapeToSvg
{
    public function convert(DefineShapeTag|DefineShape4Tag $tag): string
    {
        $shape = (new ShapeProcessor())->process($tag);

        return (new SvgShapeDrawer())->draw($shape);
    }
}
