<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Structure\Record\ImageDataType;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG3Tag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function gzcompress;

class DefineBitsJPEG3TagTest extends TestCase
{
    public const SMALL_JPEG = DefineBitsJPEG2TagTest::SMALL_JPEG;
    public const SMALL_PNG = DefineBitsJPEG2TagTest::SMALL_PNG;
    public const SMALL_GIF = DefineBitsJPEG2TagTest::SMALL_GIF;

    #[Test]
    public function readJpeg()
    {
        $reader = new SwfReader("\x21\x00\x7D\x00\x00\x00" . self::SMALL_JPEG . gzcompress("\x80"));
        $tag = DefineBitsJPEG3Tag::read($reader, $reader->end);

        $this->assertSame(33, $tag->characterId);
        $this->assertSame(self::SMALL_JPEG, $tag->imageData);
        $this->assertSame(ImageDataType::Jpeg, $tag->type);
        $this->assertSame("\x80", $tag->alphaData);
    }

    #[Test]
    public function readJpegInvalidZLibAlphaData()
    {
        $this->expectException(ParserInvalidDataException::class);
        $this->expectExceptionMessage('Invalid compressed data at offset 148: gzuncompress(): data error');

        $reader = new SwfReader("\x21\x00\x7D\x00\x00\x00" . self::SMALL_JPEG . 'invalid zlib data');
        DefineBitsJPEG3Tag::read($reader, $reader->end);
    }

    #[Test]
    public function readJpegInvalidZLibAlphaDataIgnoreError()
    {
        $reader = new SwfReader("\x21\x00\x7D\x00\x00\x00" . self::SMALL_JPEG . 'invalid zlib data', errors: 0);
        $tag = DefineBitsJPEG3Tag::read($reader, $reader->end);

        $this->assertSame(33, $tag->characterId);
        $this->assertSame(self::SMALL_JPEG, $tag->imageData);
        $this->assertSame(ImageDataType::Jpeg, $tag->type);
        $this->assertNull($tag->alphaData);
    }

    #[Test]
    public function readPng()
    {
        $reader = new SwfReader("\x21\x00\x43\x00\x00\x00" . self::SMALL_PNG);
        $tag = DefineBitsJPEG3Tag::read($reader, $reader->end);

        $this->assertSame(33, $tag->characterId);
        $this->assertSame(self::SMALL_PNG, $tag->imageData);
        $this->assertSame(ImageDataType::Png, $tag->type);
        $this->assertNull($tag->alphaData);
    }

    #[Test]
    public function readGif()
    {
        $reader = new SwfReader("\x21\x00\x2B\x00\x00\x00" . self::SMALL_GIF);
        $tag = DefineBitsJPEG3Tag::read($reader, $reader->end);

        $this->assertSame(33, $tag->characterId);
        $this->assertSame(self::SMALL_GIF, $tag->imageData);
        $this->assertSame(ImageDataType::Gif89a, $tag->type);
        $this->assertNull($tag->alphaData);
    }
}
