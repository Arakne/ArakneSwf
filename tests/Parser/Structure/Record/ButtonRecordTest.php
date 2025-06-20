<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\ButtonRecord;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class ButtonRecordTest extends ParserTestCase
{
    #[Test]
    public function readCollectionV2()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/core/core.swf', 597297);

        $records = ButtonRecord::readCollection($reader, 2);

        $this->assertCount(9, $records);
        $this->assertContainsOnlyInstancesOf(ButtonRecord::class, $records);

        $this->assertFalse($records[0]->stateHitTest);
        $this->assertFalse($records[0]->stateDown);
        $this->assertFalse($records[0]->stateOver);
        $this->assertTrue($records[0]->stateUp);
        $this->assertSame(310, $records[0]->characterId);
        $this->assertSame(1, $records[0]->placeDepth);
        $this->assertEquals(new Matrix(), $records[0]->matrix);
        $this->assertEquals(new ColorTransform(), $records[0]->colorTransform);
        $this->assertNull($records[0]->filters);
        $this->assertNull($records[0]->blendMode);

        $this->assertFalse($records[1]->stateHitTest);
        $this->assertFalse($records[1]->stateDown);
        $this->assertTrue($records[1]->stateOver);
        $this->assertTrue($records[1]->stateUp);
        $this->assertSame(312, $records[1]->characterId);
        $this->assertSame(2, $records[1]->placeDepth);
        $this->assertEquals(new Matrix(scaleX: -0.4789886474609375, scaleY: -0.4749908447265625), $records[1]->matrix);
        $this->assertEquals(new ColorTransform(redMult: 0, greenMult: 0, blueMult: 0, alphaMult: 128), $records[1]->colorTransform);
    }

    #[Test]
    public function readCollectionV1()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/../../../Extractor/Fixtures/swf1/new_theater.swf'));
        $reader->skipBytes(5200);

        $records = ButtonRecord::readCollection($reader, 1);
        $this->assertCount(4, $records);
        $this->assertContainsOnlyInstancesOf(ButtonRecord::class, $records);

        $this->assertFalse($records[0]->stateHitTest);
        $this->assertFalse($records[0]->stateDown);
        $this->assertTrue($records[0]->stateOver);
        $this->assertTrue($records[0]->stateUp);
        $this->assertSame(15, $records[0]->characterId);
        $this->assertSame(2, $records[0]->placeDepth);
        $this->assertEquals(new Matrix(scaleX: 0.4801483154296875, scaleY: 0.4801483154296875, translateX: 0, translateY: 1418), $records[0]->matrix);
        $this->assertNull($records[0]->colorTransform);
        $this->assertNull($records[0]->filters);
        $this->assertNull($records[0]->blendMode);
    }

    #[Test]
    public function readWithFilter()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/core/core.swf', 1286804);

        $records = ButtonRecord::readCollection($reader, 2);
        $this->assertCount(5, $records);

        $this->assertFalse($records[0]->stateHitTest);
        $this->assertFalse($records[0]->stateDown);
        $this->assertFalse($records[0]->stateOver);
        $this->assertTrue($records[0]->stateUp);
        $this->assertSame(561, $records[0]->characterId);
        $this->assertSame(1, $records[0]->placeDepth);
        $this->assertEquals(new Matrix(translateX: 153, translateY: 132), $records[0]->matrix);
        $this->assertEquals(new ColorTransform(), $records[0]->colorTransform);
        $this->assertCount(1, $records[0]->filters);
        $this->assertEqualsWithDelta(new ColorMatrixFilter([
            1.6836779,
            0.019124676,
            0.29719734,
            0.0,
            -159.5,
            0.1515163,
            1.9003643,
            -0.051880665,
            0.0,
            -159.5,
            -0.11656176,
            0.43133074,
            1.685231,
            0.0,
            -159.5,
            0.0,
            0.0,
            0.0,
            1.0,
            0.0,
        ]), $records[0]->filters[0], 0.00001);
    }
}
