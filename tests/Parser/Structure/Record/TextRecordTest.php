<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\GlyphEntry;
use Arakne\Swf\Parser\Structure\Record\TextRecord;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class TextRecordTest extends TestCase
{
    #[Test]
    public function readWithoutAlpha()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/../../../Extractor/Fixtures/swf1/new_theater.swf'));
        $reader->skipBytes(4349);

        $records = TextRecord::readCollection($reader, 5, 10, false);
        $this->assertCount(1, $records);
        $record = $records[0];

        $this->assertSame(1, $record->type);
        $this->assertSame(6, $record->fontId);
        $this->assertEquals(new Color(153, 153, 153), $record->color);
        $this->assertSame(278, $record->xOffset);
        $this->assertSame(424, $record->yOffset);
        $this->assertSame(440, $record->height);

        $glyphs = $record->glyphs;
        $this->assertCount(21, $glyphs);
        $this->assertContainsOnlyInstancesOf(GlyphEntry::class, $glyphs);

        $this->assertEquals(new GlyphEntry(27, 413), $glyphs[0]);
        $this->assertEquals(new GlyphEntry(15, 291), $glyphs[1]);
        $this->assertEquals(new GlyphEntry(9, 267), $glyphs[2]);
        $this->assertEquals(new GlyphEntry(0, 147), $glyphs[10]);
        $this->assertEquals(new GlyphEntry(3, 291), $glyphs[20]);
    }

    #[Test]
    public function readShouldStopAtEndOfData()
    {
        $reader = new SwfReader("\x80\x00\x80\x00\x80\x00\x80\x00");
        $records = TextRecord::readCollection($reader, 0, 0, false);

        $this->assertCount(4, $records);
    }
}
