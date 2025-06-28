<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\ImageDataType;
use Arakne\Swf\Parser\Structure\Tag\DefineBitsJPEG2Tag;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefineBitsJPEG2TagTest extends TestCase
{
    public const string SMALL_JPEG = "\xff\xd8\xff\xe0\x00\x10\x4a\x46\x49\x46\x00\x01\x01\x01\x00\x48\x00\x48\x00\x00\xff\xdb\x00\x43\x00\x03\x02\x02\x02\x02\x02\x03\x02\x02\x02\x03\x03\x03\x03\x04\x06\x04\x04\x04\x04\x04\x08\x06\x06\x05\x06\x09\x08\x0a\x0a\x09\x08\x09\x09\x0a\x0c\x0f\x0c\x0a\x0b\x0e\x0b\x09\x09\x0d\x11\x0d\x0e\x0f\x10\x10\x11\x10\x0a\x0c\x12\x13\x12\x10\x13\x0f\x10\x10\x10\xff\xc9\x00\x0b\x08\x00\x01\x00\x01\x01\x01\x11\x00\xff\xcc\x00\x06\x00\x10\x10\x05\xff\xda\x00\x08\x01\x01\x00\x00\x3f\x00\xd2\xcf\x20\xff\xd9";
    public const string SMALL_PNG = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a\x00\x00\x00\x0d\x49\x48\x44\x52\x00\x00\x00\x01\x00\x00\x00\x01\x01\x00\x00\x00\x00\x37\x6e\xf9\x24\x00\x00\x00\x0a\x49\x44\x41\x54\x78\x01\x63\x60\x00\x00\x00\x02\x00\x01\x73\x75\x01\x18\x00\x00\x00\x00\x49\x45\x4e\x44\xae\x42\x60\x82";
    public const string SMALL_GIF = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x01\x00\xff\xff\xff\x00\x00\x00\x21\xf9\x04\x01\x0a\x00\x01\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x4c\x01\x00\x3b";

    #[Test]
    public function readJpeg()
    {
        $reader = new SwfReader("\x21\x00" . self::SMALL_JPEG);
        $tag = DefineBitsJPEG2Tag::read($reader, $reader->end);

        $this->assertSame(33, $tag->characterId);
        $this->assertSame(self::SMALL_JPEG, $tag->imageData);
        $this->assertSame(ImageDataType::Jpeg, $tag->type);
    }

    #[Test]
    public function readPng()
    {
        $reader = new SwfReader("\x21\x00" . self::SMALL_PNG);
        $tag = DefineBitsJPEG2Tag::read($reader, $reader->end);

        $this->assertSame(33, $tag->characterId);
        $this->assertSame(self::SMALL_PNG, $tag->imageData);
        $this->assertSame(ImageDataType::Png, $tag->type);
    }

    #[Test]
    public function readGif()
    {
        $reader = new SwfReader("\x21\x00" . self::SMALL_GIF);
        $tag = DefineBitsJPEG2Tag::read($reader, $reader->end);

        $this->assertSame(33, $tag->characterId);
        $this->assertSame(self::SMALL_GIF, $tag->imageData);
        $this->assertSame(ImageDataType::Gif89a, $tag->type);
    }
}
