<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record\Shape;

use Arakne\Swf\Parser\Structure\Record\Shape\CurvedEdgeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\EndShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\ShapeRecord;
use Arakne\Swf\Parser\Structure\Record\Shape\StyleChangeRecord;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class ShapeRecordTest extends ParserTestCase
{
    #[Test]
    public function readCollection()
    {
        $reader = $this->createReader(__DIR__.'/../../../../Fixtures/sunAndShadow.swf', 54);

        $records = ShapeRecord::readCollection($reader, 1);

        $this->assertCount(10, $records);
        $this->assertContainsOnlyInstancesOf(ShapeRecord::class, $records);

        $this->assertEquals(new StyleChangeRecord(
            stateNewStyles: false,
            stateLineStyle: false,
            stateFillStyle0: false,
            stateFillStyle1: true,
            stateMoveTo: true,
            moveDeltaX: 0,
            moveDeltaY: -1249,
            fillStyle0: 0,
            fillStyle1: 1,
            lineStyle: 0,
            fillStyles: [],
            lineStyles: [],
        ), $records[0]);

        $this->assertEquals(new CurvedEdgeRecord(
            controlDeltaX: 517,
            controlDeltaY: 0,
            anchorDeltaX: 366,
            anchorDeltaY: 366,
        ), $records[1]);

        $this->assertEquals(new EndShapeRecord(), $records[9]);
    }

    #[Test]
    public function readCollectionV4()
    {
        $reader = $this->createReader(__DIR__.'/../../../../Fixtures/sunAndShadow.swf', 380);

        $records = ShapeRecord::readCollection($reader, 4);

        $this->assertCount(6, $records);
        $this->assertContainsOnlyInstancesOf(ShapeRecord::class, $records);

        $this->assertEquals(new StyleChangeRecord(
            stateNewStyles: false,
            stateLineStyle: true,
            stateFillStyle0: false,
            stateFillStyle1: false,
            stateMoveTo: true,
            moveDeltaX: 8000,
            moveDeltaY: 0,
            fillStyle0: 0,
            fillStyle1: 0,
            lineStyle: 1,
            fillStyles: [],
            lineStyles: [],
        ), $records[0]);

        $this->assertEquals(new EndShapeRecord(), $records[5]);
    }
}
