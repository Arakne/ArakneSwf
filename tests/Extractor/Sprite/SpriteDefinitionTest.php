<?php

namespace Arakne\Tests\Swf\Extractor\Sprite;

use Arakne\Swf\Extractor\Error\CircularReferenceException;
use Arakne\Swf\Extractor\Modifier\AbstractCharacterModifier;
use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
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

    #[Test]
    public function withAttachment()
    {
        $sprite = new SwfFile(__DIR__.'/../Fixtures/1305/1305.swf')->assetByName('anim0R');
        $other = new SwfFile(__DIR__ . '/../Fixtures/1/1.swf')->timeline(false);

        $combined = $sprite->withAttachment($other, depth: 10, name: 'attached');
        $svg = $combined->toSvg();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/1305/with-attachment.svg', $svg);
    }

    #[Test]
    public function modify()
    {
        $sprite = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf')->assetByName('anim0R');
        $sprite = $sprite->modify(new class extends AbstractCharacterModifier {
            public function applyOnShape(ShapeDefinition $shape): ShapeDefinition
            {
                return $shape->transformColors(
                    new ColorTransform(
                        redMult: 0,
                        greenMult: 0,
                        blueMult: 0,
                        alphaMult: 0,
                        redAdd: $shape->id % 3 === 0 ? 255 : 0,
                        greenAdd: $shape->id % 3 === 1 ? 255 : 0,
                        blueAdd: $shape->id % 3 === 2 ? 255 : 0,
                        alphaAdd: 255,
                    )
                );
            }

            public function applyOnSprite(SpriteDefinition $sprite): SpriteDefinition
            {
                if ($sprite->id === 27) {
                    $sprite = $sprite->withAttachment(
                        new SwfFile(__DIR__.'/../Fixtures/1435/1435.swf')->assetByName('staticR'),
                        depth: 100,
                        name: 'addedSprite',
                    );
                }

                return $sprite;
            }
        });

        $svg = $sprite->toSvg();
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/../Fixtures/1047/modified.svg', $svg);
    }

    #[Test]
    public function modifyWithoutModificationShouldReturnSameInstance()
    {
        $sprite = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf')->assetByName('anim0R');
        $this->assertSame($sprite, $sprite->modify(new class extends AbstractCharacterModifier {}));
    }
}
