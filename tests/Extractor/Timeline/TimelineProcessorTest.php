<?php

namespace Arakne\Tests\Swf\Extractor\Timeline;

use Arakne\Swf\Extractor\Error\ProcessingInvalidDataException;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Extractor\Timeline\Timeline;
use Arakne\Swf\Extractor\Timeline\TimelineProcessor;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Arakne\Swf\Parser\Structure\Tag\EndTag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObject2Tag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObjectTag;
use Arakne\Swf\Parser\Structure\Tag\ProtectTag;
use Arakne\Swf\Parser\Structure\Tag\ShowFrameTag;
use Arakne\Tests\Swf\SwfBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TimelineProcessorTest extends TestCase
{
    private SwfBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new SwfBuilder();
    }

    #[Test]
    public function unsupportedTag()
    {
        $this->expectException(ProcessingInvalidDataException::class);
        $this->expectExceptionMessage('Invalid tag type Arakne\Swf\Parser\Structure\Tag\ProtectTag in timeline');

        // 0b0001_0000 0b1000_1000 - bound  [0-1]x[0-1]
        // 0b001001_00 0b000_1_11_00 0b00_1_01_01_0 - style record + line style + move to (0 bits) + line style 1 - edge record + straight + 0 bits + general line + deltaX 1 + deltaY 1
        // 0b0010_0100 0b0001_1100 0b0010_1010
        $swf = $this->builder->createSwfFile([
            [
                DefineShapeTag::TYPE_V1,
                "\x01\x00\x10\x88" .
                "\x00" . // no fill
                "\x01\x01\x00\xFF\x00\x00" . // line style: 1px, red
                "\x01" . // numFillBits = 0, numLineBits = 1
                "\x24\x1C\x2A\x00"
            ],
            [
                DefineSpriteTag::TYPE,
                "\x02\x00\x01\x00" . $this->builder->buildTags([
                    [PlaceObjectTag::TYPE, "\x01\x00\x01\x00\x00"],
                    [ShowFrameTag::TYPE, ""],
                    [ProtectTag::TYPE, ""],
                    [EndTag::TYPE, ""],
                ])
            ]
        ]);
        $extractor = new SwfExtractor($swf);
        $sprite = $extractor->character(2);
        $this->assertInstanceOf(SpriteDefinition::class, $sprite);

        $processor = new TimelineProcessor($extractor);
        $processor->process($sprite->tag->tags);
    }

    #[Test]
    public function unsupportedTagIgnoreErrors()
    {
        // 0b0001_0000 0b1000_1000 - bound  [0-1]x[0-1]
        // 0b001001_00 0b000_1_11_00 0b00_1_01_01_0 - style record + line style + move to (0 bits) + line style 1 - edge record + straight + 0 bits + general line + deltaX 1 + deltaY 1
        // 0b0010_0100 0b0001_1100 0b0010_1010
        $swf = $this->builder->createSwfFile([
            [
                DefineShapeTag::TYPE_V1,
                "\x01\x00\x10\x88" .
                "\x00" . // no fill
                "\x01\x01\x00\xFF\x00\x00" . // line style: 1px, red
                "\x01" . // numFillBits = 0, numLineBits = 1
                "\x24\x1C\x2A\x00"
            ],
            [
                DefineSpriteTag::TYPE,
                "\x02\x00\x01\x00" . $this->builder->buildTags([
                    [PlaceObjectTag::TYPE, "\x01\x00\x01\x00\x00"],
                    [ShowFrameTag::TYPE, ""],
                    [ProtectTag::TYPE, ""],
                    [EndTag::TYPE, ""],
                ])
            ]
        ], errors: 0);
        $extractor = new SwfExtractor($swf);
        $sprite = $extractor->character(2);
        $this->assertInstanceOf(SpriteDefinition::class, $sprite);

        $processor = new TimelineProcessor($extractor);
        $timeline = $processor->process($sprite->tag->tags);

        $this->assertEquals(new Rectangle(0, 1, 0, 1), $timeline->bounds);
        $this->assertCount(1, $timeline->frames);
        $this->assertCount(1, $timeline->frames[0]->objects);
        $this->assertEquals($extractor->character(1), $timeline->frames[0]->objects[1]->object);
    }

    #[Test]
    public function newObjectMissingCharacterId()
    {
        $this->expectException(ProcessingInvalidDataException::class);
        $this->expectExceptionMessage('New object at depth 1 without characterId');

        $swf = $this->builder->createSwfFile([
            [
                PlaceObject2Tag::TYPE,
                "\x00\x01\x00"
            ],
            [EndTag::TYPE, ''],
        ]);
        $processor = new TimelineProcessor(new SwfExtractor($swf));
        $processor->process($swf->tags());
    }

    #[Test]
    public function newObjectMissingCharacterIdIgnoreError()
    {
        $swf = $this->builder->createSwfFile([
            [
                PlaceObject2Tag::TYPE,
                "\x00\x01\x00"
            ],
            [ShowFrameTag::TYPE, ''],
            [EndTag::TYPE, ''],
        ], errors: 0);
        $processor = new TimelineProcessor(new SwfExtractor($swf));
        $timeline = $processor->process($swf->tags());
        $this->assertCount(1, $timeline->frames);
        $this->assertCount(0, $timeline->frames[0]->objects);
    }

    #[Test]
    public function missingShowFrameTag()
    {
        $this->expectException(ProcessingInvalidDataException::class);
        $this->expectExceptionMessage('No frames found in the timeline: ShowFrame tag is missing');

        $swf = $this->builder->createSwfFile([
            [
                DefineShapeTag::TYPE_V1,
                "\x01\x00\x10\x88" .
                "\x00" . // no fill
                "\x01\x01\x00\xFF\x00\x00" . // line style: 1px, red
                "\x01" . // numFillBits = 0, numLineBits = 1
                "\x24\x1C\x2A\x00"
            ],
            [
                DefineSpriteTag::TYPE,
                "\x02\x00\x01\x00" . $this->builder->buildTags([
                    [PlaceObjectTag::TYPE, "\x01\x00\x01\x00\x00"],
                    [EndTag::TYPE, ""],
                ])
            ]
        ]);
        $extractor = new SwfExtractor($swf);
        $sprite = $extractor->character(2);
        $this->assertInstanceOf(SpriteDefinition::class, $sprite);

        $processor = new TimelineProcessor($extractor);
        $processor->process($sprite->tag->tags);
    }

    #[Test]
    public function missingShowFrameTagIgnore()
    {
        $swf = $this->builder->createSwfFile([
            [
                DefineShapeTag::TYPE_V1,
                "\x01\x00\x10\x88" .
                "\x00" . // no fill
                "\x01\x01\x00\xFF\x00\x00" . // line style: 1px, red
                "\x01" . // numFillBits = 0, numLineBits = 1
                "\x24\x1C\x2A\x00"
            ],
            [
                DefineSpriteTag::TYPE,
                "\x02\x00\x01\x00" . $this->builder->buildTags([
                    [PlaceObjectTag::TYPE, "\x01\x00\x01\x00\x00"],
                    [EndTag::TYPE, ""],
                ])
            ]
        ], errors: 0);
        $extractor = new SwfExtractor($swf);
        $sprite = $extractor->character(2);
        $this->assertInstanceOf(SpriteDefinition::class, $sprite);

        $processor = new TimelineProcessor($extractor);
        $this->assertEquals(Timeline::empty(), $processor->process($sprite->tag->tags));
    }
}
