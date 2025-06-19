<?php

namespace Arakne\Tests\Swf\Parser;

use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;
use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Filter\ColorMatrixFilter;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineSceneAndFrameLabelDataTag;
use Arakne\Swf\Parser\Structure\Tag\DefineSpriteTag;
use Arakne\Swf\Parser\Structure\Tag\DoActionTag;
use Arakne\Swf\Parser\Structure\Tag\EndTag;
use Arakne\Swf\Parser\Structure\Tag\PlaceObject3Tag;
use Arakne\Swf\Parser\Structure\Tag\SetBackgroundColorTag;
use Arakne\Swf\Parser\Structure\Tag\ShowFrameTag;
use Arakne\Swf\Parser\Swf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function assert;
use function file_get_contents;

class SwfTest extends TestCase
{
    #[Test]
    public function simpleVariables()
    {
        $swf = new Swf(file_get_contents(__DIR__.'/../Fixtures/simple.swf'));

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

        $this->assertEquals(new SetBackgroundColorTag(new Color(0, 0, 0)), $swf->parseTag($swf->tags[0]));

        /** @var DoActionTag $doActionTag */
        $doActionTag = $swf->parseTag($swf->tags[1]);
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

        $this->assertEquals(new ShowFrameTag(), $swf->parseTag($swf->tags[2]));
        $this->assertEquals(new EndTag(), $swf->parseTag($swf->tags[3]));
    }

    #[Test]
    public function bigValues()
    {
        $swf = new Swf(file_get_contents(__DIR__.'/../Fixtures/big.swf'));

        /** @var DoActionTag $doActionTag */
        $doActionTag = $swf->parseTag($swf->tags[1]);
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
        $swf = new Swf(file_get_contents(__DIR__.'/../Fixtures/objects.swf'));

        /** @var DoActionTag $doActionTag */
        $doActionTag = $swf->parseTag($swf->tags[1]);
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
        $swf = new Swf(file_get_contents(__DIR__.'/../Extractor/Fixtures/62/62.swf'));

        foreach ($swf->tags as $pos) {
            if ($pos->id === 19) {
                $tag = $swf->parseTag($pos);
            }
        }

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
    public function encodedU32()
    {
        $swf = new Swf(file_get_contents(__DIR__.'/../Fixtures/139.swf'));

        foreach ($swf->tags as $pos) {
            if ($pos->type === 86) {
                $tag = $swf->parseTag($pos);
                break;
            }
        }

        assert($tag instanceof DefineSceneAndFrameLabelDataTag);

        $this->assertSame([0], $tag->sceneOffsets);
        $this->assertSame([0, 4, 8, 12, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60, 64, 68, 72, 76, 86, 90, 94, 98, 102, 106, 110, 114, 118, 122, 126, 130, 134, 138, 142, 146, 150, 154, 158, 162], $tag->frameNumbers);
    }
}
