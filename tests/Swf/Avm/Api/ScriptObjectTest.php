<?php

namespace Arakne\Tests\Swf\Avm\Api;

use Arakne\Swf\Avm\Api\ScriptObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ScriptObjectTest extends TestCase
{
    public function test_simple_properties()
    {
        $o = new ScriptObject();

        $this->assertNull($o->foo);
        $this->assertNull($o['foo']);
        $this->assertFalse(isset($o->foo));
        $this->assertFalse(isset($o['foo']));
        $this->assertFalse(isset($o[false]));

        $o->foo = 123;
        $this->assertSame(123, $o->foo);
        $this->assertSame(123, $o['foo']);
        $this->assertTrue(isset($o->foo));
        $this->assertTrue(isset($o['foo']));

        $o->bar = function () use(&$args) {
            $args = func_get_args();
            return 42;
        };

        $this->assertSame(42, $o->bar('a', 'b', 'c'));
        $this->assertSame(['a', 'b', 'c'], $args);

        $o[false] = 123;
        $this->assertNull($o[false]);
        $this->assertFalse(isset($o[false]));
    }

    #[Test]
    public function computedPropertyReadOnly()
    {
        $o = new ScriptObject();
        $c = 0;
        $this->assertTrue($o->addProperty('foo', function () use(&$c) {
            return ++$c;
        }));

        $this->assertSame(1, $o->foo);
        $this->assertSame(2, $o->foo);

        $o->foo = 42;
        $this->assertSame(3, $o->foo);
    }

    #[Test]
    public function computedPropertyReadWrite()
    {
        $o = new ScriptObject();
        $this->assertTrue($o->addProperty('name', fn () => $o->firstName.' '.$o->lastName, fn ($v) => [$o->firstName, $o->lastName] = explode(' ', $v)));

        $this->assertEquals(' ', $o->name);

        $o->name = 'John Doe';
        $this->assertEquals('John Doe', $o->name);
        $this->assertEquals('John', $o->firstName);
        $this->assertEquals('Doe', $o->lastName);

        $o->firstName = 'Jane';
        $this->assertEquals('Jane Doe', $o->name);
        $this->assertEquals('Jane', $o->firstName);
        $this->assertEquals('Doe', $o->lastName);
    }

    #[Test]
    public function addPropertyInvalidName()
    {
        $o = new ScriptObject();
        $this->assertFalse($o->addProperty('', fn () => 42));

        $this->assertFalse(isset($o['']));
    }

    #[Test]
    public function toJson()
    {
        $o = new ScriptObject();
        $o->foo = 123;
        $o->addProperty('bar', fn () => 42);
        $o[21] = 'hello';

        $this->assertEquals('{"foo":123,"21":"hello","bar":42}', json_encode($o));
    }
}
