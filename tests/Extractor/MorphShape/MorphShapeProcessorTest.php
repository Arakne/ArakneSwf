<?php

namespace Arakne\Tests\Swf\Extractor\MorphShape;

use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\MorphShape\MorphShapeProcessor;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShapeTag;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_slice;
use function file_put_contents;

class MorphShapeProcessorTest extends TestCase
{
    #[Test]
    public function processEndRecordsTooSmall()
    {
        $swf = new SwfFile(__DIR__ . '/../Fixtures/homestuck/00004.swf');
        $tag = $swf->assetById(63)->tag;
        $corruptedTag = new DefineMorphShapeTag(
            $tag->characterId,
            $tag->startBounds,
            $tag->endBounds,
            $tag->offset,
            $tag->fillStyles,
            $tag->lineStyles,
            $tag->startEdges,
            array_slice($tag->endEdges, 0, 10),
        );

        $processor = new MorphShapeProcessor(new SwfExtractor($swf));
        $morphShape = $processor->process($corruptedTag);
        $shape = $morphShape->interpolate(32768);

        $this->assertCount(1, $shape->paths);
        $this->assertCount(14, $shape->paths[0]->edges);

        $renderer = new SvgCanvas($tag->startBounds);
        $renderer->shape($shape);

        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/homestuck/morphshape_corrupted_too_small_end.svg',
            $renderer->render(),
        );
    }
}
