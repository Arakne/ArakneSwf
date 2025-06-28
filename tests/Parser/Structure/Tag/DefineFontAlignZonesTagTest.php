<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\DefineFontAlignZonesTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefineFontAlignZonesTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../Fixtures/Examples1.swf', 115);
        $tag = DefineFontAlignZonesTag::read($reader, 128);

        $this->assertSame(1, $tag->fontId);
        $this->assertSame(1, $tag->csmTableHint);
        $this->assertCount(1, $tag->zoneTable);
        $this->assertCount(2, $tag->zoneTable[0]->data);
        $this->assertSame(0.2005615234375, $tag->zoneTable[0]->data[0]->alignmentCoordinate);
        $this->assertSame(0.0, $tag->zoneTable[0]->data[0]->range);
        $this->assertSame(0.0, $tag->zoneTable[0]->data[1]->alignmentCoordinate);
        $this->assertSame(1.716796875, $tag->zoneTable[0]->data[1]->range);
        $this->assertTrue($tag->zoneTable[0]->maskX);
        $this->assertTrue($tag->zoneTable[0]->maskY);
    }
}
