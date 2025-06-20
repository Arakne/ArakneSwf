<?php

namespace Arakne\Tests\Swf\Parser\Structure\Action;

use Arakne\Swf\Parser\Structure\Action\DefineFunction2Data;
use Arakne\Swf\Parser\Structure\Action\DefineFunctionData;
use Arakne\Swf\Parser\Structure\Action\GetURL2Data;
use Arakne\Swf\Parser\Structure\Action\GetURLData;
use Arakne\Swf\Parser\Structure\Action\GotoFrame2Data;
use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;
use Arakne\Swf\Parser\Structure\Action\WaitForFrameData;
use Arakne\Swf\Parser\SwfReader;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

use function var_dump;

use const _PHPStan_ea7072c0a\__;

class OpcodeTest extends ParserTestCase
{
    #[Test]
    public function readDataGoToFrame()
    {
        $reader = new SwfReader("\x01\x02");
        $this->assertSame(513, Opcode::ActionGotoFrame->readData($reader, 2));
    }

    #[Test]
    public function readDataGetURL()
    {
        $reader = new SwfReader("http://example.com\0target\0");
        $this->assertEquals(new GetURLData(
            'http://example.com',
            'target',
        ), Opcode::ActionGetURL->readData($reader, 2));
    }

    #[Test]
    public function readDataStoreRegister()
    {
        $reader = new SwfReader("\x42");
        $this->assertSame(66, Opcode::ActionStoreRegister->readData($reader, 1));
    }

    #[Test]
    public function readDataConstantPool()
    {
        $reader = new SwfReader("\x03\x00foo\0bar\0baz\0");
        $this->assertSame(['foo', 'bar', 'baz'], Opcode::ActionConstantPool->readData($reader, 14));
    }

    #[Test]
    public function readDataWaitForFrame()
    {
        $reader = new SwfReader("\x01\x02\x03");
        $this->assertEquals(new WaitForFrameData(513, 3), Opcode::ActionWaitForFrame->readData($reader, 3));
    }

    #[Test]
    public function readDataSetTarget()
    {
        $reader = new SwfReader("target\0");
        $this->assertSame('target', Opcode::ActionSetTarget->readData($reader, 7));
    }

    #[Test]
    public function readDataGoToLabel()
    {
        $reader = new SwfReader("label\0");
        $this->assertSame('label', Opcode::ActionGoToLabel->readData($reader, 6));
    }

    #[Test]
    public function readDataWaitForFrame2()
    {
        $reader = new SwfReader("\x03");
        $this->assertSame(3, Opcode::ActionWaitForFrame2->readData($reader, 1));
    }

    #[Test]
    public function readDataDefineFunction2()
    {
        $reader = $this->createReader(__DIR__ . '/../../../Fixtures/sunAndShadow.swf', 1150);

        $data = Opcode::ActionDefineFunction2->readData($reader, 24);

        $this->assertInstanceOf(DefineFunction2Data::class, $data);
        $this->assertSame('', $data->name); // Method has no name, it's a class member
        $this->assertSame(['coord1', 'coord2'], $data->parameters);
        $this->assertSame([6, 5], $data->registers);
        $this->assertSame(7, $data->registerCount);
        $this->assertFalse($data->preloadParentFlag);
        $this->assertFalse($data->preloadRootFlag);
        $this->assertTrue($data->suppressSuperFlag);
        $this->assertFalse($data->preloadSuperFlag);
        $this->assertTrue($data->suppressArgumentsFlag);
        $this->assertFalse($data->preloadArgumentsFlag);
        $this->assertTrue($data->suppressThisFlag);
        $this->assertFalse($data->preloadThisFlag);
        $this->assertFalse($data->preloadGlobalFlag);
    }

    #[Test]
    public function readDataWith()
    {
        $reader = new SwfReader("\x03\x00foo");
        $this->assertSame("foo", Opcode::ActionWith->readData($reader, 5));
    }

    #[Test]
    public function readDataActionPush()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/simple.swf', 101);

        $this->assertEquals([
            new Value(Type::Constant8, 0),
            new Value(Type::Integer, 123),
        ], Opcode::ActionPush->readData($reader, 7));
    }

    #[Test]
    public function readDataActionJump()
    {
        $reader = new SwfReader("\x02\x00");
        $this->assertSame(2, Opcode::ActionJump->readData($reader, 2));
    }

    #[Test]
    public function readDataActionIf()
    {
        $reader = new SwfReader("\x02\x00");
        $this->assertSame(2, Opcode::ActionIf->readData($reader, 2));
    }

    #[Test]
    public function readDataGetURL2()
    {
        $reader = new SwfReader("\x81");
        $this->assertEquals(new GetURL2Data(
            sendVarsMethod: 2,
            loadTargetFlag: false,
            loadVariablesFlag: true,
        ), Opcode::ActionGetURL2->readData($reader, 1));
    }

    #[Test]
    public function readDataDefineFunction()
    {
        $reader = $this->createReader(__DIR__.'/../../../Fixtures/function.swf', 82);

        $data = Opcode::ActionDefineFunction->readData($reader, 25);

        $this->assertInstanceOf(DefineFunctionData::class, $data);
        $this->assertSame('myFunction', $data->name);
        $this->assertSame(['arg1', 'arg2'], $data->parameters);
        $this->assertSame(137, $data->codeSize);
    }

    #[Test]
    public function readDataGotoFrame2()
    {
        $reader = new SwfReader("\x02\x42\x00");
        $this->assertEquals(
            new GotoFrame2Data(
                sceneBiasFlag: true,
                playFlag: false,
                sceneBias: 66,
            ),
            Opcode::ActionGotoFrame2->readData($reader, 3)
        );
    }

    #[Test]
    public function readDataUnsupportedOpcode()
    {
        $this->expectExceptionMessage('Unexpected data for opcode ActionAdd, actionLength=42');
        Opcode::ActionAdd->readData(new SwfReader(""), 42);
    }
}
