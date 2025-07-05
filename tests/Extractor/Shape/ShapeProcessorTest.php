<?php

namespace Arakne\Tests\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Error\ProcessingInvalidDataException;
use Arakne\Swf\Extractor\Image\EmptyImage;
use Arakne\Swf\Extractor\Shape\FillType\Bitmap;
use Arakne\Swf\Extractor\Shape\FillType\Solid;
use Arakne\Swf\Extractor\Shape\Path;
use Arakne\Swf\Extractor\Shape\ShapeProcessor;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Record\Shape\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\FillStyle;
use Arakne\Swf\Parser\Structure\Record\Shape\ShapeWithStyle;
use Arakne\Swf\Parser\Structure\Record\Shape\StraightEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StyleChangeRecord;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;
use function var_dump;

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

    #[Test]
    public function processWithInvalidFillStyle()
    {
        $this->expectException(ProcessingInvalidDataException::class);
        $this->expectExceptionMessage('Unknown fill style: 5');

        $swf = new SwfFile(__DIR__.'/../Fixtures/shape.swf');
        $processor = new ShapeProcessor(new SwfExtractor($swf));
        $tag = new DefineShapeTag(
            1,
            1,
            new Rectangle(0, 1, 0, 1),
            new ShapeWithStyle(
                fillStyles: [new FillStyle(type: 5)],
                lineStyles: [],
                shapeRecords: [
                    new StyleChangeRecord(
                        stateNewStyles: false,
                        stateLineStyle: false,
                        stateFillStyle0: false,
                        stateFillStyle1: true,
                        stateMoveTo: false,
                        moveDeltaX: 0,
                        moveDeltaY: 0,
                        fillStyle0: 0,
                        fillStyle1: 1,
                        lineStyle: 0,
                        fillStyles: [],
                        lineStyles: [],
                    ),
                    new StraightEdgeRecord(false, false, 1, 0),
                    new StraightEdgeRecord(false, true, 0, 1),
                    new StraightEdgeRecord(false, false, -1, 0),
                    new StraightEdgeRecord(false, true, 0, -1),
                    new EndShapeRecord(),
                ]
            )
        );

        $processor->process($tag);
    }

    #[Test]
    public function processWithInvalidFillStyleIgnore()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/shape.swf', errors: 0);
        $processor = new ShapeProcessor(new SwfExtractor($swf));
        $tag = new DefineShapeTag(
            1,
            1,
            new Rectangle(0, 1, 0, 1),
            new ShapeWithStyle(
                fillStyles: [new FillStyle(type: 5)],
                lineStyles: [],
                shapeRecords: [
                    new StyleChangeRecord(
                        stateNewStyles: false,
                        stateLineStyle: false,
                        stateFillStyle0: false,
                        stateFillStyle1: true,
                        stateMoveTo: false,
                        moveDeltaX: 0,
                        moveDeltaY: 0,
                        fillStyle0: 0,
                        fillStyle1: 1,
                        lineStyle: 0,
                        fillStyles: [],
                        lineStyles: [],
                    ),
                    new StraightEdgeRecord(false, false, 1, 0),
                    new StraightEdgeRecord(false, true, 0, 1),
                    new StraightEdgeRecord(false, false, -1, 0),
                    new StraightEdgeRecord(false, true, 0, -1),
                    new EndShapeRecord(),
                ]
            )
        );

        $shape = $processor->process($tag);
        $this->assertCount(1, $shape->paths);
        $this->assertEquals(new Solid(new Color(0, 0, 0, 0)), $shape->paths[0]->style->fill);
    }

    #[Test]
    public function processWithInvalidBitmap()
    {
        $this->expectException(ProcessingInvalidDataException::class);
        $this->expectExceptionMessage('The character 404 is not a valid image character');

        $swf = new SwfFile(__DIR__.'/../Fixtures/shape.swf');
        $processor = new ShapeProcessor(new SwfExtractor($swf));
        $tag = new DefineShapeTag(
            1,
            1,
            new Rectangle(0, 1, 0, 1),
            new ShapeWithStyle(
                fillStyles: [
                    new FillStyle(
                        type: FillStyle::CLIPPED_BITMAP,
                        bitmapId: 404,
                        bitmapMatrix: new Matrix(),
                    ),
                ],
                lineStyles: [],
                shapeRecords: [
                    new StyleChangeRecord(
                        stateNewStyles: false,
                        stateLineStyle: false,
                        stateFillStyle0: false,
                        stateFillStyle1: true,
                        stateMoveTo: false,
                        moveDeltaX: 0,
                        moveDeltaY: 0,
                        fillStyle0: 0,
                        fillStyle1: 1,
                        lineStyle: 0,
                        fillStyles: [],
                        lineStyles: [],
                    ),
                    new StraightEdgeRecord(false, false, 1, 0),
                    new StraightEdgeRecord(false, true, 0, 1),
                    new StraightEdgeRecord(false, false, -1, 0),
                    new StraightEdgeRecord(false, true, 0, -1),
                    new EndShapeRecord(),
                ]
            )
        );

        $processor->process($tag);
    }

    #[Test]
    public function processWithInvalidBitmapIgnoreError()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/shape.swf', errors: 0);
        $processor = new ShapeProcessor(new SwfExtractor($swf));
        $tag = new DefineShapeTag(
            1,
            1,
            new Rectangle(0, 1, 0, 1),
            new ShapeWithStyle(
                fillStyles: [
                    new FillStyle(
                        type: FillStyle::CLIPPED_BITMAP,
                        bitmapId: 404,
                        bitmapMatrix: new Matrix(),
                    ),
                ],
                lineStyles: [],
                shapeRecords: [
                    new StyleChangeRecord(
                        stateNewStyles: false,
                        stateLineStyle: false,
                        stateFillStyle0: false,
                        stateFillStyle1: true,
                        stateMoveTo: false,
                        moveDeltaX: 0,
                        moveDeltaY: 0,
                        fillStyle0: 0,
                        fillStyle1: 1,
                        lineStyle: 0,
                        fillStyles: [],
                        lineStyles: [],
                    ),
                    new StraightEdgeRecord(false, false, 1, 0),
                    new StraightEdgeRecord(false, true, 0, 1),
                    new StraightEdgeRecord(false, false, -1, 0),
                    new StraightEdgeRecord(false, true, 0, -1),
                    new EndShapeRecord(),
                ]
            )
        );

        $shape = $processor->process($tag);
        $this->assertCount(1, $shape->paths);
        $this->assertEquals(new Bitmap(
            bitmap: new EmptyImage(404),
            matrix: new Matrix(),
        ), $shape->paths[0]->style->fill);
    }
}
