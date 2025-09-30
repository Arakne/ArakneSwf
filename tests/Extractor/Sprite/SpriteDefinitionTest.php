<?php

namespace Arakne\Tests\Swf\Extractor\Sprite;

use Arakne\Swf\Extractor\Error\CircularReferenceException;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Arakne\Swf\Parser\Structure\Tag\EndTag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObjectTag;
use Arakne\Swf\Parser\Structure\Tag\ShowFrameTag;
use Arakne\Swf\SwfFile;
use Arakne\Tests\Swf\SwfBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_put_contents;

class SpriteDefinitionTest extends TestCase
{
    private SwfBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new SwfBuilder();
    }

    #[Test]
    public function circularReference()
    {
        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage('Circular reference detected while processing sprite 1');

        $swf = $this->builder->createSwfFile([
            [
                DefineSpriteTag::TYPE,
                "\x01\x00\x01\x00" . $this->builder->buildTags([
                    [PlaceObjectTag::TYPE, "\x01\x00\x01\x00\x00"],
                    [ShowFrameTag::TYPE, ""],
                    [EndTag::TYPE, ""],
                ])
            ]
        ]);
        $sprite = $swf->assetById(1);

        $this->assertInstanceOf(SpriteDefinition::class, $sprite);
        $sprite->timeline();
    }

    #[Test]
    public function indirectCircularReference()
    {
        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage('Circular reference detected while processing sprite 1');

        $swf = $this->builder->createSwfFile([
            [
                DefineSpriteTag::TYPE,
                "\x01\x00\x01\x00" . $this->builder->buildTags([
                    [PlaceObjectTag::TYPE, "\x02\x00\x01\x00\x00"],
                    [ShowFrameTag::TYPE, ""],
                    [EndTag::TYPE, ""],
                ])
            ],
            [
                DefineSpriteTag::TYPE,
                "\x02\x00\x01\x00" . $this->builder->buildTags([
                    [PlaceObjectTag::TYPE, "\x01\x00\x01\x00\x00"],
                    [ShowFrameTag::TYPE, ""],
                    [EndTag::TYPE, ""],
                ])
            ],
        ]);
        $sprite = $swf->assetById(1);

        $this->assertInstanceOf(SpriteDefinition::class, $sprite);
        $sprite->timeline();
    }

    #[Test]
    public function circularReferenceIgnoreError()
    {
        $swf = $this->builder->createSwfFile([
            [
                DefineSpriteTag::TYPE,
                "\x01\x00\x01\x00" . $this->builder->buildTags([
                    [PlaceObjectTag::TYPE, "\x01\x00\x01\x00\x00"],
                    [ShowFrameTag::TYPE, ""],
                    [EndTag::TYPE, ""],
                ])
            ]
        ], errors: 0);
        $sprite = $swf->assetById(1);

        $this->assertInstanceOf(SpriteDefinition::class, $sprite);
        $timeline = $sprite->timeline();

        $this->assertCount(1, $timeline->frames);
        $this->assertEmpty($timeline->frames[0]->objects);
        $this->assertEmpty($timeline->frames[0]->actions);
    }

    #[Test]
    public function indirectCircularReferenceIgnoreError()
    {
        $swf = $this->builder->createSwfFile([
            [
                DefineSpriteTag::TYPE,
                "\x01\x00\x01\x00" . $this->builder->buildTags([
                    [PlaceObjectTag::TYPE, "\x02\x00\x01\x00\x00"],
                    [ShowFrameTag::TYPE, ""],
                    [EndTag::TYPE, ""],
                ])
            ],
            [
                DefineSpriteTag::TYPE,
                "\x02\x00\x01\x00" . $this->builder->buildTags([
                    [PlaceObjectTag::TYPE, "\x01\x00\x01\x00\x00"],
                    [ShowFrameTag::TYPE, ""],
                    [EndTag::TYPE, ""],
                ])
            ],
        ], errors: 0);
        $sprite = $swf->assetById(1);

        $this->assertInstanceOf(SpriteDefinition::class, $sprite);
        $timeline = $sprite->timeline();

        $this->assertCount(1, $timeline->frames);
        $this->assertEmpty($timeline->frames[0]->objects);
        $this->assertEmpty($timeline->frames[0]->actions);
    }

    #[Test]
    public function toSvgWithoutSubpixelStrokeWidth()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/../Fixtures/1305/1305.swf'));
        $sprite = $extractor->byName('anim0R');

        $this->assertInstanceOf(SpriteDefinition::class, $sprite);

        $svg = $sprite->toSvg(subpixelStrokeWidth: false);
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/1305/without-subpixel-stroke-width.svg', $svg);
    }
}
