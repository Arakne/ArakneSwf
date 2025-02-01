<?php

namespace Arakne\Tests\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Shape\ShapeToSvg;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function file_put_contents;

class ShapeToSvgFunctionalTest extends TestCase
{
    #[Test, TestWith(['shape.swf', 'shape.svg'])]
    public function singleShape(string $swf, string $svg): void
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/' . $swf);

        $converter = new ShapeToSvg();

        /** @var DefineShapeTag|DefineShape4Tag $tag */
        foreach ($swf->tags(2, 22, 32, 83) as $tag) {
            $shape = $converter->convert($tag);
            file_put_contents(__DIR__.'/../Fixtures/test.svg', $shape);
            break;
        }

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/' . $svg, $shape);
    }
}
