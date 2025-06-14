<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\ClipEventFlags;
use Arakne\Swf\Parser\SwfReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClipEventFlagsTest extends TestCase
{
    #[Test]
    public function readSwf5()
    {
        $reader = new SwfReader("\x29\x98");
        $flags = ClipEventFlags::read($reader, 5);

        $this->assertFalse($flags->has(ClipEventFlags::KEY_UP));
        $this->assertFalse($flags->has(ClipEventFlags::KEY_DOWN));
        $this->assertTrue($flags->has(ClipEventFlags::MOUSE_UP));
        $this->assertFalse($flags->has(ClipEventFlags::MOUSE_DOWN));
        $this->assertTrue($flags->has(ClipEventFlags::MOUSE_MOVE));
        $this->assertFalse($flags->has(ClipEventFlags::UNLOAD));
        $this->assertFalse($flags->has(ClipEventFlags::ENTER_FRAME));
        $this->assertTrue($flags->has(ClipEventFlags::LOAD));

        $this->assertTrue($flags->has(ClipEventFlags::DRAG_OVER));
        $this->assertFalse($flags->has(ClipEventFlags::ROLL_OUT));
        $this->assertFalse($flags->has(ClipEventFlags::ROLL_OVER));
        $this->assertTrue($flags->has(ClipEventFlags::RELEASE_OUTSIDE));
        $this->assertTrue($flags->has(ClipEventFlags::RELEASE));
        $this->assertFalse($flags->has(ClipEventFlags::PRESS));
        $this->assertFalse($flags->has(ClipEventFlags::INITIALIZE));
        $this->assertFalse($flags->has(ClipEventFlags::DATA));

        $this->assertFalse($flags->has(ClipEventFlags::CONSTRUCT));
        $this->assertFalse($flags->has(ClipEventFlags::KEY_PRESS));
        $this->assertFalse($flags->has(ClipEventFlags::DRAG_OUT));
    }

    #[Test]
    public function readSwf7()
    {
        $reader = new SwfReader("\x29\x98\x03\x00");
        $flags = ClipEventFlags::read($reader, 6);

        $this->assertFalse($flags->has(ClipEventFlags::KEY_UP));
        $this->assertFalse($flags->has(ClipEventFlags::KEY_DOWN));
        $this->assertTrue($flags->has(ClipEventFlags::MOUSE_UP));
        $this->assertFalse($flags->has(ClipEventFlags::MOUSE_DOWN));
        $this->assertTrue($flags->has(ClipEventFlags::MOUSE_MOVE));
        $this->assertFalse($flags->has(ClipEventFlags::UNLOAD));
        $this->assertFalse($flags->has(ClipEventFlags::ENTER_FRAME));
        $this->assertTrue($flags->has(ClipEventFlags::LOAD));

        $this->assertTrue($flags->has(ClipEventFlags::DRAG_OVER));
        $this->assertFalse($flags->has(ClipEventFlags::ROLL_OUT));
        $this->assertFalse($flags->has(ClipEventFlags::ROLL_OVER));
        $this->assertTrue($flags->has(ClipEventFlags::RELEASE_OUTSIDE));
        $this->assertTrue($flags->has(ClipEventFlags::RELEASE));
        $this->assertFalse($flags->has(ClipEventFlags::PRESS));
        $this->assertFalse($flags->has(ClipEventFlags::INITIALIZE));
        $this->assertFalse($flags->has(ClipEventFlags::DATA));

        $this->assertFalse($flags->has(ClipEventFlags::CONSTRUCT));
        $this->assertTrue($flags->has(ClipEventFlags::KEY_PRESS));
        $this->assertTrue($flags->has(ClipEventFlags::DRAG_OUT));
    }
}
