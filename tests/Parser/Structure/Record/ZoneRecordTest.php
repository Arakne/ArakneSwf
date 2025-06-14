<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\ZoneData;
use Arakne\Swf\Parser\Structure\Record\ZoneRecord;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class ZoneRecordTest extends TestCase
{
    #[Test]
    public function readCollection()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/../../../Extractor/Fixtures/core/core.swf'));
        $reader->doUncompress(10000000);
        $reader->skipBytes(103680);

        $records = ZoneRecord::readCollection($reader, 114780);
        $this->assertCount(1110, $records);

        $this->assertCount(2, $records[0]->data);
        $this->assertEqualsWithDelta([
            new ZoneData(0, 0),
            new ZoneData(0, 0),
        ], $records[0]->data, 0.00001);
        $this->assertFalse($records[0]->maskY);
        $this->assertFalse($records[0]->maskX);

        $this->assertCount(2, $records[1]->data);
        $this->assertEqualsWithDelta([
            new ZoneData(0, 0),
            new ZoneData(0, 1.7255859375),
        ], $records[1]->data, 0.00001);
        $this->assertTrue($records[1]->maskY);
        $this->assertTrue($records[1]->maskX);

        $this->assertCount(2, $records[2]->data);
        $this->assertEqualsWithDelta([
            new ZoneData(0.428955078125, 0),
            new ZoneData(0.49365234375, 1.759765625),
        ], $records[2]->data, 0.00001);
        $this->assertTrue($records[2]->maskY);
        $this->assertTrue($records[2]->maskX);
    }
}
