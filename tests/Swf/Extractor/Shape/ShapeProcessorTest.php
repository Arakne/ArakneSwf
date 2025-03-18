<?php

namespace Arakne\Tests\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\ShapeProcessor;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

class ShapeProcessorTest extends TestCase
{
    #[Test]
    public function process()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/shape.swf');
        $processor = new ShapeProcessor(new SwfExtractor($swf));
        $tag = iterator_to_array($swf->tags(DefineShapeTag::TYPE_V2), false)[0];

        $shape = $processor->process($tag);

        $this->assertSame(940, $shape->width);
        $this->assertSame(960, $shape->height);
        $this->assertSame(-5034, $shape->xOffset);
        $this->assertSame(-3519, $shape->yOffset);
        $this->assertCount(33, $shape->paths);
        $this->assertContainsOnly(Path::class, $shape->paths);
    }
}
