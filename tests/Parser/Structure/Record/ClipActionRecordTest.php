<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\ClipActionRecord;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClipActionRecordTest extends TestCase
{

    #[Test]
    public function readCollectionShouldStopAtEndOfData()
    {
        $reader = new SwfReader("\x01\x00\x00\x00\x00\x00\x01\x00\x00\x00\x00\x00\x01\x00\x00\x00\x00\x00\x01\x00\x00\x00\x00\x00\x01\x00\x00\x00\x00\x00");
        $records = ClipActionRecord::readCollection($reader, 3);

        $this->assertCount(5, $records);
    }
}
