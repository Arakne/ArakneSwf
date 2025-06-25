<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\ImageDataType;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG4Tag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function gzcompress;

class DefineBitsJPEG4TagTest extends TestCase
{
    public const SMALL_JPEG = DefineBitsJPEG2TagTest::SMALL_JPEG;
    public const SMALL_PNG = DefineBitsJPEG2TagTest::SMALL_PNG;
    public const SMALL_GIF = DefineBitsJPEG2TagTest::SMALL_GIF;

    #[Test]
    public function readJpeg()
    {
        $reader = new SwfReader("\x21\x00\x7D\x00\x00\x00\x12\x34" . self::SMALL_JPEG . gzcompress("\x80"));
        $tag = DefineBitsJPEG4Tag::read($reader, $reader->end);

        $this->assertSame(33, $tag->characterId);
        $this->assertSame(13330, $tag->deblockParam);
        $this->assertSame(self::SMALL_JPEG, $tag->imageData);
        $this->assertSame(ImageDataType::Jpeg, $tag->type);
        $this->assertSame("\x80", $tag->alphaData);
    }

    #[Test]
    public function readPng()
    {
        $reader = new SwfReader("\x21\x00\x43\x00\x00\x00\x12\x34" . self::SMALL_PNG);
        $tag = DefineBitsJPEG4Tag::read($reader, $reader->end);

        $this->assertSame(33, $tag->characterId);
        $this->assertSame(13330, $tag->deblockParam);
        $this->assertSame(self::SMALL_PNG, $tag->imageData);
        $this->assertSame(ImageDataType::Png, $tag->type);
        $this->assertNull($tag->alphaData);
    }

    #[Test]
    public function readGif()
    {
        $reader = new SwfReader("\x21\x00\x2B\x00\x00\x00\x12\x34" . self::SMALL_GIF);
        $tag = DefineBitsJPEG4Tag::read($reader, $reader->end);

        $this->assertSame(33, $tag->characterId);
        $this->assertSame(13330, $tag->deblockParam);
        $this->assertSame(self::SMALL_GIF, $tag->imageData);
        $this->assertSame(ImageDataType::Gif89a, $tag->type);
        $this->assertNull($tag->alphaData);
    }
}
