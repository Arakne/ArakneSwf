<?php

namespace Arakne\Tests\Swf\Parser;

use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\SwfTag;
use Arakne\Swf\Parser\Structure\Tag\DefineSceneAndFrameLabelDataTag;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Arakne\Swf\Parser\Structure\Tag\DoActionTag;
use Arakne\Swf\Parser\Structure\Tag\EndTag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObject3Tag;
use Arakne\Swf\Parser\Structure\Tag\SetBackgroundColorTag;
use Arakne\Swf\Parser\Structure\Tag\ShowFrameTag;
use Arakne\Swf\Parser\Swf;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

use function assert;
use function file_get_contents;
use function gzcompress;
use function pack;
use function substr;

class SwfTest extends TestCase
{
    #[Test]
    public function simpleVariables()
    {
        $swf = Swf::fromString(file_get_contents(__DIR__.'/../Fixtures/simple.swf'));

        $this->assertSame(6, $swf->header->version);
        $this->assertSame('CWS', $swf->header->signature);
        $this->assertSame(50.0, $swf->header->frameRate);
        $this->assertSame(1, $swf->header->frameCount);
        $this->assertEquals(new Rectangle(
            xmin: 0,
            xmax: 20,
            ymin: 0,
            ymax: 20,
        ), $swf->header->frameSize);

        $this->assertCount(4, $swf->tags);
        $this->assertSame(9, $swf->tags[0]->type);
        $this->assertSame(12, $swf->tags[1]->type);
        $this->assertSame(1, $swf->tags[2]->type);
        $this->assertSame(0, $swf->tags[3]->type);

        $this->assertEquals(new SetBackgroundColorTag(new Color(0, 0, 0)), $swf->parse($swf->tags[0]));

        /** @var DoActionTag $doActionTag */
        $doActionTag = $swf->parse($swf->tags[1]);
        $this->assertInstanceOf(DoActionTag::class, $doActionTag);
        $this->assertCount(12, $doActionTag->actions);

        $this->assertSame(Opcode::ActionConstantPool, $doActionTag->actions[0]->opcode);
        $this->assertSame(['simple_int', 'simple_string', 'abc', 'simple_float', 'simple_bool', 'simple_null'], $doActionTag->actions[0]->data);

        $this->assertSame(Opcode::ActionPush, $doActionTag->actions[1]->opcode);
        $this->assertEquals([new Value(Type::Constant8, 0), new Value(Type::Integer, 123)], $doActionTag->actions[1]->data);
        $this->assertSame(Opcode::ActionSetVariable, $doActionTag->actions[2]->opcode);
        $this->assertNull($doActionTag->actions[2]->data);

        $this->assertSame(Opcode::ActionPush, $doActionTag->actions[3]->opcode);
        $this->assertEquals([new Value(Type::Constant8, 1), new Value(Type::Constant8, 2)], $doActionTag->actions[3]->data);
        $this->assertSame(Opcode::ActionSetVariable, $doActionTag->actions[4]->opcode);
        $this->assertNull($doActionTag->actions[4]->data);

        $this->assertSame(Opcode::ActionPush, $doActionTag->actions[5]->opcode);
        $this->assertEquals([new Value(Type::Constant8, 3), new Value(Type::Double, 1.23)], $doActionTag->actions[5]->data);
        $this->assertSame(Opcode::ActionSetVariable, $doActionTag->actions[6]->opcode);
        $this->assertNull($doActionTag->actions[6]->data);

        $this->assertSame(Opcode::ActionPush, $doActionTag->actions[7]->opcode);
        $this->assertEquals([new Value(Type::Constant8, 4), new Value(Type::Boolean, true)], $doActionTag->actions[7]->data);
        $this->assertSame(Opcode::ActionSetVariable, $doActionTag->actions[8]->opcode);
        $this->assertNull($doActionTag->actions[8]->data);

        $this->assertSame(Opcode::ActionPush, $doActionTag->actions[9]->opcode);
        $this->assertEquals([new Value(Type::Constant8, 5), new Value(Type::Null, null)], $doActionTag->actions[9]->data);
        $this->assertSame(Opcode::ActionSetVariable, $doActionTag->actions[10]->opcode);
        $this->assertNull($doActionTag->actions[10]->data);

        $this->assertSame(Opcode::Null, $doActionTag->actions[11]->opcode);

        $this->assertEquals(new ShowFrameTag(), $swf->parse($swf->tags[2]));
        $this->assertEquals(new EndTag(), $swf->parse($swf->tags[3]));
    }

    #[Test]
    public function bigValues()
    {
        $swf = Swf::fromString(file_get_contents(__DIR__.'/../Fixtures/big.swf'));

        /** @var DoActionTag $doActionTag */
        $doActionTag = $swf->parse($swf->tags[1]);
        $this->assertInstanceOf(DoActionTag::class, $doActionTag);
        $this->assertCount(10, $doActionTag->actions);

        $this->assertSame(Opcode::ActionConstantPool, $doActionTag->actions[0]->opcode);
        $this->assertSame(['big_int', 'negative_int', 'big_float', 'negative_float'], $doActionTag->actions[0]->data);

        $this->assertSame(Opcode::ActionPush, $doActionTag->actions[1]->opcode);
        $this->assertEquals([new Value(Type::Constant8, 0), new Value(Type::Integer, 1234567890)], $doActionTag->actions[1]->data);
        $this->assertSame(Opcode::ActionSetVariable, $doActionTag->actions[2]->opcode);
        $this->assertNull($doActionTag->actions[2]->data);

        $this->assertSame(Opcode::ActionPush, $doActionTag->actions[3]->opcode);
        $this->assertEquals([new Value(Type::Constant8, 1), new Value(Type::Integer, -1234567890)], $doActionTag->actions[3]->data);
        $this->assertSame(Opcode::ActionSetVariable, $doActionTag->actions[4]->opcode);
        $this->assertNull($doActionTag->actions[4]->data);

        $this->assertSame(Opcode::ActionPush, $doActionTag->actions[5]->opcode);
        $this->assertEquals([new Value(Type::Constant8, 2), new Value(Type::Double, 1234567890123.1235)], $doActionTag->actions[5]->data);
        $this->assertSame(Opcode::ActionSetVariable, $doActionTag->actions[6]->opcode);
        $this->assertNull($doActionTag->actions[6]->data);

        $this->assertSame(Opcode::ActionPush, $doActionTag->actions[7]->opcode);
        $this->assertEquals([new Value(Type::Constant8, 3), new Value(Type::Double, -1234567890123.1235)], $doActionTag->actions[7]->data);
        $this->assertSame(Opcode::ActionSetVariable, $doActionTag->actions[8]->opcode);
        $this->assertNull($doActionTag->actions[8]->data);

        $this->assertSame(Opcode::Null, $doActionTag->actions[9]->opcode);
    }

    #[Test]
    public function objects()
    {
        $swf = Swf::fromString(file_get_contents(__DIR__.'/../Fixtures/objects.swf'));

        /** @var DoActionTag $doActionTag */
        $doActionTag = $swf->parse($swf->tags[1]);
        $this->assertInstanceOf(DoActionTag::class, $doActionTag);
        $this->assertCount(45, $doActionTag->actions);

        $actions = [];

        foreach ($doActionTag->actions as $action) {
            $actions[] = $action->opcode->name.'('.json_encode($action->data).')';
        }

        $this->assertSame(<<<'ACTIONS'
            ActionConstantPool(["bag","Object","a","b","arr","Array","inlined_object","c","hello","d","inlined_array","get_member","array_access","get_member_str"])
            ActionPush([{"type":8,"value":0},{"type":7,"value":0},{"type":8,"value":1}])
            ActionNewObject(null)
            ActionSetVariable(null)
            ActionPush([{"type":8,"value":0}])
            ActionGetVariable(null)
            ActionPush([{"type":8,"value":2},{"type":7,"value":1}])
            ActionSetMember(null)
            ActionPush([{"type":8,"value":0}])
            ActionGetVariable(null)
            ActionPush([{"type":8,"value":3},{"type":5,"value":false}])
            ActionSetMember(null)
            ActionPush([{"type":8,"value":4},{"type":7,"value":0},{"type":8,"value":5}])
            ActionNewObject(null)
            ActionSetVariable(null)
            ActionPush([{"type":8,"value":4}])
            ActionGetVariable(null)
            ActionPush([{"type":7,"value":0},{"type":7,"value":1}])
            ActionSetMember(null)
            ActionPush([{"type":8,"value":4}])
            ActionGetVariable(null)
            ActionPush([{"type":7,"value":1},{"type":7,"value":2}])
            ActionSetMember(null)
            ActionPush([{"type":8,"value":6},{"type":8,"value":7},{"type":6,"value":1.3},{"type":8,"value":9},{"type":8,"value":8},{"type":7,"value":2}])
            ActionInitObject(null)
            ActionSetVariable(null)
            ActionPush([{"type":8,"value":10},{"type":7,"value":3},{"type":7,"value":2},{"type":7,"value":1},{"type":7,"value":3}])
            ActionInitArray(null)
            ActionSetVariable(null)
            ActionPush([{"type":8,"value":11},{"type":8,"value":0}])
            ActionGetVariable(null)
            ActionPush([{"type":8,"value":2}])
            ActionGetMember(null)
            ActionSetVariable(null)
            ActionPush([{"type":8,"value":12},{"type":8,"value":4}])
            ActionGetVariable(null)
            ActionPush([{"type":7,"value":1}])
            ActionGetMember(null)
            ActionSetVariable(null)
            ActionPush([{"type":8,"value":13},{"type":8,"value":0}])
            ActionGetVariable(null)
            ActionPush([{"type":8,"value":3}])
            ActionGetMember(null)
            ActionSetVariable(null)
            Null(null)
            ACTIONS,
            implode("\n", $actions)
        );
    }

    #[Test]
    public function parseFloat()
    {
        $swf = Swf::fromString(file_get_contents(__DIR__.'/../Extractor/Fixtures/62/62.swf'));

        $tag = $swf->parse($swf->dictionary[19]);
        assert($tag instanceof DefineSpriteTag);

        foreach ($tag->tags as $placeObject) {
            if (
                $placeObject instanceof PlaceObject3Tag
                && $placeObject->surfaceFilterList[0] instanceof ColorMatrixFilter
            ) {
                $matrix = $placeObject->surfaceFilterList[0]->matrix;
            }
        }

        $this->assertEqualsWithDelta([
            0.6462849,
            0.9110194,
            -0.30730438,
            0.0,
            109.12499,
            0.21911979,
            0.8362024,
            0.19467786,
            0.0,
            109.125,
            0.6046222,
            0.3182575,
            0.32712013,
            0.0,
            109.12499,
            0.0,
            0.0,
            0.0,
            1.0,
            0.0,
        ], $matrix, 0.00001);
    }

    #[Test]
    public function dictionary()
    {
        $swf = Swf::fromString(file_get_contents(__DIR__.'/../Extractor/Fixtures/62/62.swf'));
        $this->assertCount(19, $swf->dictionary);
        $this->assertContainsOnlyInstancesOf(SwfTag::class, $swf->dictionary);

        $this->assertSame(DefineShape4Tag::TYPE_V4, $swf->dictionary[1]->type);
        $this->assertSame(DefineSpriteTag::TYPE, $swf->dictionary[19]->type);
    }

    #[Test]
    public function encodedU32()
    {
        $swf = Swf::fromString(file_get_contents(__DIR__.'/../Fixtures/139.swf'));

        foreach ($swf->tags as $pos) {
            if ($pos->type === 86) {
                $tag = $swf->parse($pos);
                break;
            }
        }

        assert($tag instanceof DefineSceneAndFrameLabelDataTag);

        $this->assertSame([0], $tag->sceneOffsets);
        $this->assertSame([0, 4, 8, 12, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60, 64, 68, 72, 76, 86, 90, 94, 98, 102, 106, 110, 114, 118, 122, 126, 130, 134, 138, 142, 146, 150, 154, 158, 162], $tag->frameNumbers);
    }

    #[Test]
    public function fromStringInvalidSignature()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported SWF signature: inv');

        Swf::fromString('invalid signature');
    }

    #[Test]
    public function fromStringInvalidFileLength()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid SWF file length: 5');

        Swf::fromString("FWS\x01\x05\x00\x00\x00\x00\x00");
    }

    #[Test]
    public function fromStringSimpleNotCompressed()
    {
        $swf = Swf::fromString("FWS\x01\x10\x00\x00\x00\x0F\x80\x00\x01\x01\x00\x00\x00");

        $this->assertSame('FWS', $swf->header->signature);
        $this->assertSame(1, $swf->header->version);
        $this->assertSame(16, $swf->header->fileLength);
        $this->assertEquals(new Rectangle(
            xmin: -1,
            xmax: -1,
            ymin: -1,
            ymax: -1,
        ), $swf->header->frameSize);
        $this->assertSame(1.0, $swf->header->frameRate);
        $this->assertSame(1, $swf->header->frameCount);
        $this->assertEquals([new SwfTag(0, 16, 0)], $swf->tags);
    }

    #[Test]
    public function fromStringSimpleCompressed()
    {
        $before = "CWS\x01\x10\x00\x00\x00";
        $body = "\x0F\x80\x00\x01\x01\x00\x00\x00";
        $swf = Swf::fromString($before . gzcompress($body, 9));

        $this->assertSame('CWS', $swf->header->signature);
        $this->assertSame(1, $swf->header->version);
        $this->assertSame(16, $swf->header->fileLength);
        $this->assertEquals(new Rectangle(
            xmin: -1,
            xmax: -1,
            ymin: -1,
            ymax: -1,
        ), $swf->header->frameSize);
        $this->assertSame(1.0, $swf->header->frameRate);
        $this->assertSame(1, $swf->header->frameCount);
        $this->assertEquals([new SwfTag(0, 16, 0)], $swf->tags);
    }

    #[Test]
    public function fuzzingIgnoreErrors()
    {
        $randomizer = new Randomizer(new Xoshiro256StarStar());

        for ($i = 0; $i < 10; ++$i) {
            $size = 1_000_000;
            $data = "FWS\x01" . pack('V', $size) . $randomizer->getBytes($size);

            $swf = Swf::fromString($data, errors: 0);

            $this->assertSame('FWS', $swf->header->signature);
            $this->assertSame(1, $swf->header->version);
            $this->assertSame($size, $swf->header->fileLength);

            foreach ($swf->tags as $tag) {
                $swf->parse($tag);
            }
        }
    }

    #[Test]
    public function truncatedSwf()
    {
        $content = file_get_contents(__DIR__.'/../Extractor/Fixtures/core/core.swf');
        $truncated = Swf::fromString(substr($content, 0, 1_000_000), errors: 0);
        $valid = Swf::fromString($content, errors: 0);

        $this->assertCount(1349, $truncated->tags);

        foreach ($truncated->tags as $index => $tag) {
            $this->assertEquals($valid->tags[$index], $tag);

            // Check only small tags for performance reasons
            if ($tag->length < 5000) {
                $this->assertEquals($valid->parse($valid->tags[$index]), $truncated->parse($tag));
            }
        }
    }
}
