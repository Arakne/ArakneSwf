<?php

namespace Arakne\Tests\Swf\Parser;

use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Error\ParserOutOfBoundException;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;

use function file_get_contents;
use function gzcompress;
use function str_repeat;

class SwfReaderTest extends ParserTestCase
{
    #[Test]
    public function readBytes()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));

        $this->assertSame('FWS', $reader->readBytes(3));
        $this->assertSame(3, $reader->offset);
        $this->assertSame("\x11\x32", $reader->readBytes(2));
        $this->assertSame(5, $reader->offset);
    }

    #[Test]
    public function readBytesOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Cannot read 4 bytes from offset 0, end is at 2');

        new SwfReader("abcd", 2)->readBytes(4);
    }

    #[Test]
    public function readBytesOverflowIgnoreError()
    {
        $this->assertSame("ab\0\0", new SwfReader("abcd", 2, 0)->readBytes(4));
    }

    #[Test]
    public function uncompress()
    {
        $data = "CWF\x05\xFF\x00\x00\x00" . gzcompress(str_repeat('a', 247));
        $reader = new SwfReader($data);
        $reader->skipBytes(8);

        $newReader = $reader->uncompress(255);

        $this->assertNotEquals($reader, $newReader);
        $this->assertSame(8, $newReader->offset);
        $this->assertSame("CWF\x05\xFF\x00\x00\x00" . str_repeat('a', 247), $newReader->data);
    }

    #[Test]
    public function uncompressWithoutLength()
    {
        $data = "CWF\x05\xFF\x00\x00\x00" . gzcompress(str_repeat('a', 247));
        $reader = new SwfReader($data);
        $reader->skipBytes(8);

        $newReader = $reader->uncompress();

        $this->assertNotEquals($reader, $newReader);
        $this->assertSame(8, $newReader->offset);
        $this->assertSame("CWF\x05\xFF\x00\x00\x00" . str_repeat('a', 247), $newReader->data);
    }

    #[Test]
    public function uncompressDataTooLong()
    {
        $this->expectException(ParserInvalidDataException::class);
        $this->expectExceptionMessage('Invalid compressed data at offset 8: gzuncompress(): insufficient memory');

        $data = "CWF\x05\xFF\x00\x00\x00" . gzcompress(str_repeat('a', 247));
        $reader = new SwfReader($data);
        $reader->skipBytes(8);

        $reader->uncompress(100);
    }

    #[Test]
    public function chunk()
    {
        $reader = new SwfReader('abcdefghijklmnopqrstuvwxyz');
        $reader->skipBytes(3);

        $chunk = $reader->chunk(10, 15);

        $this->assertNotEquals($reader, $chunk);
        $this->assertSame(10, $chunk->offset);
        $this->assertSame(3, $reader->offset);
    }

    #[Test]
    public function chunkOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 15, end: 13)');

        $reader = new SwfReader('abcdefghijklmnopqrstuvwxyz', 13);
        $reader->chunk(10, 15);
    }

    #[Test]
    public function chunkOverflowIgnoreError()
    {
        $reader = new SwfReader('abcdefghijklmnopqrstuvwxyz', 13, 0);
        $chunk = $reader->chunk(10, 15);
        $this->assertSame(10, $chunk->offset);
        $this->assertSame(13, $chunk->end);
    }

    #[Test]
    public function readBytesTo()
    {
        $reader = new SwfReader('abcdefghijklmnopqrstuvwxyz');

        $this->assertSame('abcd', $reader->readBytesTo(4));
        $this->assertSame(4, $reader->offset);

        $this->assertSame('efgh', $reader->readBytesTo(8));
        $this->assertSame(8, $reader->offset);

        $this->assertSame('', $reader->readBytesTo(8));
        $this->assertSame(8, $reader->offset);

        try {
            $reader->readBytesTo(5);
        } catch (ParserOutOfBoundException $e) {
            $this->assertEquals('Cannot read bytes to an offset before the current offset: 5 < 8', $e->getMessage());
        }

        $this->assertSame(8, $reader->offset);
    }

    #[Test]
    public function readBytesToOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 0, end: 10)');

        $reader = new SwfReader('abcdefghijklmnopqrstuvwxyz', 10);
        $reader->readBytesTo(11);
    }

    #[Test]
    public function readBytesToOverflowIgnoreError()
    {
        $reader = new SwfReader('abcdefghijklmnopqrstuvwxyz', 10, 0);
        $this->assertSame('abcdefghij', $reader->readBytesTo(11));
    }

    #[Test]
    public function readZlibTo()
    {
        $reader = new SwfReader(gzcompress('abcdefghijklmnopqrstuvwxyz'));
        $this->assertSame('abcdefghijklmnopqrstuvwxyz', $reader->readZlibTo($reader->end));
    }

    #[Test]
    public function readZlibToEmpty()
    {
        $reader = new SwfReader('');
        $this->assertSame('', $reader->readZlibTo($reader->end));
    }

    #[Test]
    public function readZlibToOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 0, end: 34)');

        $reader = new SwfReader(gzcompress('abcdefghijklmnopqrstuvwxyz'));
        $reader->readZlibTo(50);
    }

    #[Test]
    public function readZlibToOverflowIgnoreError()
    {
        $reader = new SwfReader(gzcompress('abcdefghijklmnopqrstuvwxyz'), errors: 0);
        $this->assertSame('abcdefghijklmnopqrstuvwxyz', $reader->readZlibTo(50));
    }

    #[Test]
    public function readZlibToInvalidZlibData()
    {
        $this->expectException(ParserInvalidDataException::class);
        $this->expectExceptionMessage('Invalid compressed data at offset 26: gzuncompress(): data error');

        $reader = new SwfReader('abcdefghijklmnopqrstuvwxyz');
        $reader->readZlibTo(26);
    }

    #[Test]
    public function readZlibToInvalidZlibDataIgnoreError()
    {
        $reader = new SwfReader('abcdefghijklmnopqrstuvwxyz', errors: 0);
        $this->assertSame('', $reader->readZlibTo(26));
    }

    #[Test]
    public function skipBytes()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));

        $reader->skipBytes(3);
        $this->assertSame(3, $reader->offset);
        $reader->skipBytes(2);
        $this->assertSame(5, $reader->offset);
        $this->assertSame(194, $reader->readUI8());
    }

    #[Test]
    public function skipTo()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));

        $reader->skipBytes(3);
        $this->assertSame(3, $reader->offset);
        $reader->skipTo(25);
        $this->assertSame(25, $reader->offset);
    }

    #[Test]
    public function readChar()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));

        $this->assertSame('F', $reader->readChar());
        $this->assertSame(1, $reader->offset);
        $this->assertSame('W', $reader->readChar());
        $this->assertSame(2, $reader->offset);
        $this->assertSame('S', $reader->readChar());
    }

    #[Test]
    public function readCharOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 2, end: 2)');

        $reader = new SwfReader('abc', 2);
        $reader->skipBytes(2);
        $reader->readChar();
    }

    #[Test]
    public function readCharOverflowIgnoreError()
    {
        $reader = new SwfReader('abc', 2, 0);
        $reader->skipBytes(2);
        $this->assertSame("\0", $reader->readChar());
    }

    #[Test]
    public function readUB()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));
        $reader->skipBytes(8);

        $this->assertSame(16, $reader->readUB(5));
        $this->assertSame(8, $reader->offset);

        $this->assertSame(0, $reader->readUB(16));
        $this->assertSame(10, $reader->offset);

        $this->assertSame(13300, $reader->readUB(16));
        $this->assertSame(12, $reader->offset);

        $this->assertSame(0, $reader->readUB(16));
        $this->assertSame(14, $reader->offset);

        $this->assertSame(17600, $reader->readUB(16));
        $this->assertSame(16, $reader->offset);

        $this->assertSame(0, $reader->readUB(0));
        $this->assertSame(16, $reader->offset);

        $this->assertSame(0, $reader->readUB(3));
        $this->assertSame(17, $reader->offset);
    }

    #[Test]
    public function readUBOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 2, end: 2)');

        $reader = new SwfReader('abcdefghijklmnopqrstuvwxyz', 2);
        $reader->readUB(17);
    }

    #[Test]
    public function readUBOverflowIgnoreError()
    {
        $reader = new SwfReader('abcdefghijklmnopqrstuvwxyz', 2, 0);
        $this->assertSame(49860, $reader->readUB(17));
    }

    #[Test]
    public function skipBits()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));
        $reader->skipBytes(8);
        $reader->skipBits(21);
        $this->assertSame(10, $reader->offset);

        $this->assertSame(13300, $reader->readUB(16));
    }

    #[Test]
    public function alignByte()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));
        $reader->skipBytes(8);

        $reader->alignByte();
        $this->assertSame(8, $reader->offset);

        $reader->skipBits(21);
        $reader->alignByte();
        $this->assertSame(11, $reader->offset);
    }

    #[Test]
    public function readNullTerminatedString()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));
        $reader->skipBytes(573);
        $this->assertSame('_184_fla.MainTimeline', $reader->readNullTerminatedString());

        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));
        $reader->skipBytes(59);
        $this->assertSame('', $reader->readNullTerminatedString());
    }

    #[Test]
    public function readNullTerminatedStringOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 573, end: 580)');

        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'), 580);
        $reader->skipBytes(573);
        $reader->readNullTerminatedString();
    }

    #[Test]
    public function readNullTerminatedStringOverflowIgnoreError()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'), 580, 0);
        $reader->skipBytes(573);
        $this->assertSame('_184_fl', $reader->readNullTerminatedString());
    }

    #[Test]
    public function readNullTerminatedStringMissingNull()
    {
        $this->expectException(ParserInvalidDataException::class);
        $this->expectExceptionMessage('String terminator not found');

        $reader = new SwfReader("foo bar");
        $reader->readNullTerminatedString();
    }

    #[Test]
    public function readNullTerminatedStringMissingNullIgnoreError()
    {
        $reader = new SwfReader("foo bar", errors: 0);
        $this->assertSame('foo bar', $reader->readNullTerminatedString());
    }

    #[Test]
    public function readFB()
    {
        $reader = $this->createReader(__DIR__.'/../Fixtures/1317.swf', 1341);
        $reader->skipBits(6);

        $this->assertEquals(-1.1363677978515625, $reader->readFB(18));
        $this->assertEquals(1.1363677978515625, $reader->readFB(18));
        $this->assertSame(0.0, $reader->readFB(0));
    }

    #[Test]
    public function readFBOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 2, end: 2)');

        $reader = new SwfReader("\x00\x00\x00\x00", 2);
        $reader->readFB(17);
    }

    #[Test]
    public function readFBOverflowIgnoreError()
    {
        $reader = new SwfReader("\x00\x00\x00\x00", 2, 0);
        $this->assertSame(0.0, $reader->readFB(17));
    }

    #[Test]
    public function readBool()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));
        $reader->skipBytes(23);

        $this->assertFalse($reader->readBool());
        $this->assertFalse($reader->readBool());
        $this->assertFalse($reader->readBool());
        $this->assertFalse($reader->readBool());
        $this->assertTrue($reader->readBool());
        $this->assertFalse($reader->readBool());
        $this->assertFalse($reader->readBool());
        $this->assertFalse($reader->readBool());
        $this->assertSame(24, $reader->offset);
    }

    #[Test]
    public function readBoolOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 1, end: 1)');

        $reader = new SwfReader("\x00\x00\x00\x00", 1);
        $reader->skipBits(8);
        $reader->readBool();
    }

    #[Test]
    public function readBoolOverflowIgnoreError()
    {
        $reader = new SwfReader("\x00\x00\x00\x00", 1, 0);
        $reader->skipBits(8);
        $this->assertFalse($reader->readBool());
    }

    #[Test]
    public function readSB()
    {
        $reader = $this->createReader(__DIR__.'/../Fixtures/1317.swf', 34);
        $reader->skipBits(5);

        $this->assertSame(-37, $reader->readSB(7));
        $this->assertSame(37, $reader->readSB(7));
        $this->assertSame(-42, $reader->readSB(7));
        $this->assertSame(51, $reader->readSB(7));
        $this->assertSame(0, $reader->readSB(0));
    }

    #[Test]
    public function readSBOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 2, end: 2)');

        $reader = new SwfReader("\x00\x00\x00\x00", 2);
        $reader->readSB(17);
    }

    #[Test]
    public function readSBOverflowIgnoreError()
    {
        $reader = new SwfReader("\x00\x00\x00\x00", 2, 0);
        $this->assertSame(0, $reader->readSB(17));
    }

    #[Test]
    public function readFixed8()
    {
        $reader = $this->createReader(__DIR__.'/../Extractor/Fixtures/1700/1700.swf', 83196);
        $this->assertEquals(0.00390625, $reader->readFixed8());

        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));
        $reader->skipBytes(17);
        $this->assertEquals(24.0, $reader->readFixed8());

        $this->assertEquals(7.5, new SwfReader("\x80\x07")->readFixed8());
        $this->assertEquals(-0.7421875, new SwfReader("\x42\xFF")->readFixed8());
        $this->assertEquals(-28.87109375, new SwfReader("\x21\xE3")->readFixed8());
    }

    #[Test]
    public function readFixed8Overflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 2, end: 2)');

        $reader = new SwfReader("\x00\x00\x00\x00", 2);
        $reader->skipBytes(1);
        $reader->readFixed8();
    }

    #[Test]
    public function readFixed8OverflowIgnoreError()
    {
        $reader = new SwfReader("\x00\x00\x00\x00", 2, 0);
        $reader->skipBytes(1);
        $this->assertSame(0.0, $reader->readFixed8());
    }

    #[Test]
    public function readFixed()
    {
        $reader = $this->createReader(__DIR__.'/../Extractor/Fixtures/54/54.swf', 6861);

        $this->assertEquals(23.0, $reader->readFixed());
        $this->assertEquals(23.0, $reader->readFixed());
        $this->assertEquals(0.7853851318359375, $reader->readFixed());
        $this->assertEquals(0.0, $reader->readFixed());

        $this->assertEquals(7.5, new SwfReader("\x00\x80\x07\x00")->readFixed());
        $this->assertEquals(-237.49969482421875, new SwfReader("\x14\x80\x12\xFF")->readFixed());
    }

    #[Test]
    public function readFixedOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Cannot read 4 bytes from offset 0, end is at 2');

        $reader = new SwfReader("\x00\x00\x00\x00", 2);
        $reader->readFixed();
    }

    #[Test]
    public function readFixedOverflowIgnoreError()
    {
        $reader = new SwfReader("\x00\x00\x00\x00", 2, 0);
        $this->assertSame(0.0, $reader->readFixed());
    }

    #[Test]
    public function readFloat16()
    {
        $reader = $this->createReader(__DIR__.'/../Extractor/Fixtures/core/core.swf', 103681);

        $this->assertEquals(0.0, $reader->readFloat16());
        $this->assertEquals(0.0, $reader->readFloat16());
        $this->assertEquals(0.0, $reader->readFloat16());
        $this->assertEquals(0.0, $reader->readFloat16());

        $reader->skipBytes(2);
        $this->assertEquals(0.0, $reader->readFloat16());
        $this->assertEquals(0.0, $reader->readFloat16());
        $this->assertEquals(0.0, $reader->readFloat16());
        $this->assertEquals(1.7255859375, $reader->readFloat16());

        $this->assertSame(0.0, new SwfReader("\x00\x00")->readFloat16());
        $this->assertSame('0', (string) new SwfReader("\x00\x00")->readFloat16());
        $this->assertSame(-0.0, new SwfReader("\x00\x80")->readFloat16());
        $this->assertSame('-0', (string) new SwfReader("\x00\x80")->readFloat16());
        $this->assertSame(2.9802322387695312E-8, new SwfReader("\x01\x00")->readFloat16());
        $this->assertSame(-2.9802322387695312E-8, new SwfReader("\x01\x80")->readFloat16());
        $this->assertSame(3.0517578125E-5, new SwfReader("\x00\x04")->readFloat16());
        $this->assertSame(0.5, new SwfReader("\x00\x3C")->readFloat16());
        $this->assertSame(1.0, new SwfReader("\x00\x40")->readFloat16());
        $this->assertSame(-1.0, new SwfReader("\x00\xC0")->readFloat16());
        $this->assertSame(2.0, new SwfReader("\x00\x44")->readFloat16());
        $this->assertSame(INF, new SwfReader("\x00\x7C")->readFloat16());
        $this->assertSame(-INF, new SwfReader("\x00\xFC")->readFloat16());
        $this->assertNan(new SwfReader("\x00\x7E")->readFloat16());
    }

    #[Test]
    public function readFloat16Overflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 1, end: 1)');

        $reader = new SwfReader("\x00\x00", 1);
        $reader->readFloat16();
    }

    #[Test]
    public function readFloat16OverflowIgnoreError()
    {
        $reader = new SwfReader("\x12\x00", 1, 0);
        $this->assertSame(5.364418029785156E-7, $reader->readFloat16());
    }

    #[Test]
    public function readFloat()
    {
        $reader = $this->createReader(__DIR__.'/../Extractor/Fixtures/62/62.swf', 5584);

        $this->assertEqualsWithDelta(1.06, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(-9.109997, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(1.06, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(-9.10997, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(1.06, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(-9.10997, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(1.0, $reader->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(0.0, $reader->readFloat(), 0.0001);
    }

    #[Test]
    public function readFloatOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Cannot read 4 bytes from offset 0, end is at 1');

        $reader = new SwfReader("\x00\x00", 1);
        $reader->readFloat();
    }

    #[Test]
    public function readFloatOverflowIgnoreError()
    {
        $reader = new SwfReader("\x00\x00", 1, 0);
        $this->assertSame(0.0, $reader->readFloat());
    }

    #[Test]
    public function readDouble()
    {
        $reader = $this->createReader(__DIR__.'/../Fixtures/big.swf', 106);

        $this->assertSame(1234567890123.1235, $reader->readDouble());

        $reader->skipBytes(7);
        $this->assertSame(-1234567890123.1235, $reader->readDouble());
    }

    #[Test]
    public function readDoubleOverflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Cannot read 4 bytes from offset 0, end is at 1');

        $reader = new SwfReader("\x00\x00", 1);
        $reader->readDouble();
    }

    #[Test]
    public function readDoubleOverflowIgnoreError()
    {
        $reader = new SwfReader("\x00\x00", 1, 0);
        $this->assertSame(0.0, $reader->readDouble());
    }

    #[Test]
    public function readUI8()
    {
        $reader = $this->createReader(__DIR__.'/Fixtures/Examples1.swf', 4467);

        $this->assertSame(255, $reader->readUI8());
        $this->assertSame(153, $reader->readUI8());
        $this->assertSame(204, $reader->readUI8());
        $this->assertSame(255, $reader->readUI8());
    }

    #[Test]
    public function readUI8Overflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 1, end: 1)');

        $reader = new SwfReader("\x00\x00", 1);
        $reader->skipBytes(1);
        $reader->readUI8();
    }

    #[Test]
    public function readUI8OverflowIgnoreError()
    {
        $reader = new SwfReader("\x00\x42", 1, 0);
        $reader->skipBytes(1);
        $this->assertSame(0, $reader->readUI8());
    }

    #[Test]
    public function readSI16()
    {
        $this->assertSame(0, new SwfReader("\x00\x00")->readSI16());
        $this->assertSame(1, new SwfReader("\x01\x00")->readSI16());
        $this->assertSame(-1, new SwfReader("\xFF\xFF")->readSI16());
        $this->assertSame(32767, new SwfReader("\xFF\x7F")->readSI16());
        $this->assertSame(-32768, new SwfReader("\x00\x80")->readSI16());
        $this->assertSame(12345, new SwfReader("\x39\x30")->readSI16());
        $this->assertSame(-12345, new SwfReader("\xC7\xCF")->readSI16());
    }

    #[Test]
    public function readSI16Overflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 1, end: 1)');

        $reader = new SwfReader("\x00\x00", 1);
        $reader->readSI16();
    }

    #[Test]
    public function readSI16OverflowIgnoreError()
    {
        $reader = new SwfReader("\x12\x34", 1, 0);
        $this->assertSame(18, $reader->readSI16());
    }

    #[Test]
    public function readUI16()
    {
        $this->assertSame(0, new SwfReader("\x00\x00")->readUI16());
        $this->assertSame(1, new SwfReader("\x01\x00")->readUI16());
        $this->assertSame(65535, new SwfReader("\xFF\xFF")->readUI16());
        $this->assertSame(32767, new SwfReader("\xFF\x7F")->readUI16());
        $this->assertSame(32768, new SwfReader("\x00\x80")->readUI16());
        $this->assertSame(12345, new SwfReader("\x39\x30")->readUI16());
        $this->assertSame(53191, new SwfReader("\xC7\xCF")->readUI16());
    }

    #[Test]
    public function readUI16Overflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 1, end: 1)');

        $reader = new SwfReader("\x00\x00", 1);
        $reader->readUI16();
    }

    #[Test]
    public function readUI16OverflowIgnoreError()
    {
        $reader = new SwfReader("\x12\x34", 1, 0);
        $this->assertSame(18, $reader->readUI16());
    }

    #[Test]
    public function readSI32()
    {
        $reader = $this->createReader(__DIR__.'/../Fixtures/big.swf', 84);

        $this->assertSame(1234567890, $reader->readSI32());
        $reader->skipBytes(7);
        $this->assertSame(-1234567890, $reader->readSI32());
    }

    #[Test]
    public function readSI32Overflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Cannot read 4 bytes from offset 0, end is at 1');

        $reader = new SwfReader("\x00\x00", 1);
        $reader->readSI32();
    }

    #[Test]
    public function readSI32OverflowIgnoreError()
    {
        $reader = new SwfReader("\x12\x34", errors: 0);
        $this->assertSame(13330, $reader->readSI32());
    }

    #[Test]
    public function readUI32()
    {
        $reader = new SwfReader(file_get_contents(__DIR__.'/Fixtures/uncompressed.swf'));

        $reader->skipBytes(4);
        $this->assertSame(180786, $reader->readUI32());

        $reader->skipBytes(4014);
        $this->assertSame(28698, $reader->readUI32());
    }

    #[Test]
    public function readUI32Overflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Cannot read 4 bytes from offset 0, end is at 1');

        $reader = new SwfReader("\x00\x00", 1);
        $reader->readUI32();
    }

    #[Test]
    public function readUI32OverflowIgnoreError()
    {
        $reader = new SwfReader("\x12\x34", errors: 0);
        $this->assertSame(13330, $reader->readUI32());
    }

    #[Test]
    public function readSI64()
    {
        $this->assertSame(0, new SwfReader("\x00\x00\x00\x00\x00\x00\x00\x00")->readSI64());
        $this->assertSame(1, new SwfReader("\x01\x00\x00\x00\x00\x00\x00\x00")->readSI64());
        $this->assertSame(-1, new SwfReader("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF")->readSI64());
        $this->assertSame(9223372036854775807, new SwfReader("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x7F")->readSI64());
        $this->assertSame((int) -9223372036854775808, new SwfReader("\x00\x00\x00\x00\x00\x00\x00\x80")->readSI64()); // Cast to int is required to avoid automatic cast to float
        $this->assertSame(1234, new SwfReader("\xD2\x04\x00\x00\x00\x00\x00\x00")->readSI64());
    }

    #[Test]
    public function readSI64Overflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Cannot read 8 bytes from offset 0, end is at 1');

        $reader = new SwfReader("\x00\x00", 1);
        $reader->readSI64();
    }

    #[Test]
    public function readSI64OverflowIgnoreError()
    {
        $reader = new SwfReader("\x12\x34", errors: 0);
        $this->assertSame(13330, $reader->readSI64());
    }

    #[Test]
    public function readEncodedU32()
    {
        $reader = $this->createReader(__DIR__.'/../Fixtures/139.swf', 38);

        $this->assertSame(1, $reader->readEncodedU32());
        $this->assertSame(39, $reader->offset);
        $this->assertSame(0, $reader->readEncodedU32());
        $this->assertSame(40, $reader->offset);

        $reader->skipBytes(190);
        $this->assertSame(158, $reader->readEncodedU32());
        $this->assertSame(232, $reader->offset);

        $this->assertSame(0, new SwfReader("\x00")->readEncodedU32());
        $this->assertSame(42, new SwfReader("\x2A")->readEncodedU32());
        $this->assertSame(127, new SwfReader("\x7F")->readEncodedU32());
        $this->assertSame(128, new SwfReader("\x80\x01")->readEncodedU32());
        $this->assertSame(255, new SwfReader("\xFF\x01")->readEncodedU32());
        $this->assertSame(5503, new SwfReader("\xFF\x2A")->readEncodedU32());
        $this->assertSame(32_767, new SwfReader("\xFF\xFF\x01")->readEncodedU32());
        $this->assertSame(2_097_152, new SwfReader("\x80\x80\x80\x01")->readEncodedU32());
        $this->assertSame(268_435_456, new SwfReader("\x80\x80\x80\x80\x01")->readEncodedU32());
        $this->assertSame(4_294_967_295, new SwfReader("\xFF\xFF\xFF\xFF\x0F")->readEncodedU32());
        $this->assertSame(34_359_738_367, new SwfReader("\xFF\xFF\xFF\xFF\x7F")->readEncodedU32());
    }

    #[Test]
    public function readEncodedU32Overflow()
    {
        $this->expectException(ParserOutOfBoundException::class);
        $this->expectExceptionMessage('Trying to access data after the end of the input stream (offset: 2, end: 2)');

        $reader = new SwfReader("\x80\x80\x01", 2);
        $reader->readEncodedU32();
    }

    #[Test]
    public function readEncodedU32OverflowIgnoreError()
    {
        $reader = new SwfReader("\x84\x85\x01", 2, 0);
        $this->assertSame(644, $reader->readEncodedU32());
    }
}
