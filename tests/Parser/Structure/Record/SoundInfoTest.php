<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\SoundEnvelope;
use Arakne\Swf\Parser\Structure\Record\SoundInfo;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class SoundInfoTest extends TestCase
{
    #[Test]
    public function read()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/../../../Extractor/Fixtures/swf1/new_theater.swf'));
        $reader->skipBytes(14020);

        $this->assertEquals(new SoundInfo(
            syncStop: false,
            syncNoMultiple: false,
            inPoint: null,
            outPoint: null,
            loopCount: null,
            envelopes: [
                new SoundEnvelope(
                    pos44: 1638,
                    leftLevel: 32768,
                    rightLevel: 32768
                ),
                new SoundEnvelope(
                    pos44: 2185,
                    leftLevel: 0,
                    rightLevel: 0
                ),
            ]
        ), SoundInfo::read($reader));
    }

    #[Test]
    public function readEmpty()
    {
        $reader = new SwfReader("\x00");

        $this->assertEquals(new SoundInfo(
            syncStop: false,
            syncNoMultiple: false,
            inPoint: null,
            outPoint: null,
            loopCount: null,
            envelopes: []
        ), SoundInfo::read($reader));
    }

    #[Test]
    public function readAllFlags()
    {
        $reader = new SwfReader("\x3F\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x00");

        $this->assertEquals(new SoundInfo(
            syncStop: true,
            syncNoMultiple: true,
            inPoint: 67305985,
            outPoint: 134678021,
            loopCount: 2569,
            envelopes: []
        ), SoundInfo::read($reader));
    }
}
