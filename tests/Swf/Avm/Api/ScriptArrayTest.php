<?php

namespace Arakne\Tests\Swf\Avm\Api;

use Arakne\Swf\Avm\Api\ScriptArray;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ScriptArrayTest extends TestCase
{
    #[Test]
    public function empty()
    {
        $a = new ScriptArray();

        $this->assertSame(0, $a->length);
        $this->assertSame('[]', json_encode($a));

        $this->assertNull($a[0]);
        $this->assertNull($a->foo);
        $this->assertFalse(isset($a[0]));
        $this->assertFalse(isset($a->{0}));
    }

    #[Test]
    public function singleConstructorParameter()
    {
        $a = new ScriptArray(3);

        $this->assertSame(3, $a->length);
        $this->assertSame('[null,null,null]', json_encode($a));
    }

    #[Test]
    public function arrayConstructor()
    {
        $a = new ScriptArray(1, 2, 3);

        $this->assertSame(3, $a->length);
        $this->assertSame('[1,2,3]', json_encode($a));
        $this->assertSame(1, $a[0]);
        $this->assertSame(2, $a[1]);
        $this->assertSame(3, $a[2]);
        $this->assertTrue(isset($a[0]));
        $this->assertTrue(isset($a[1]));
        $this->assertTrue(isset($a[2]));
        $this->assertFalse(isset($a[3]));
    }

    #[Test]
    public function setLength()
    {
        $a = new ScriptArray(1, 2, 3);
        $a->length = 5;

        $this->assertSame(5, $a->length);
        $this->assertSame('[1,2,3,null,null]', json_encode($a));

        $a->length = 2;
        $this->assertSame(2, $a->length);
        $this->assertSame('[1,2]', json_encode($a));

        $a->length = 0;
        $this->assertSame(0, $a->length);
        $this->assertSame('[]', json_encode($a));
    }

    #[Test]
    public function offsetSet()
    {
        $a = new ScriptArray();

        $a[0] = 'foo';
        $a[1] = 'bar';
        $a[2] = 'baz';

        $this->assertSame(3, $a->length);
        $this->assertSame('["foo","bar","baz"]', json_encode($a));
    }

    #[Test]
    public function unsetShouldNotModifyLength()
    {
        $a = new ScriptArray(1, 2, 3);

        unset($a[1]);

        $this->assertSame(3, $a->length);
        $this->assertSame('[1,null,3]', json_encode($a));
    }

    #[Test]
    public function integerFloatIndex()
    {
        $a = new ScriptArray();

        $a[0] = 'foo';
        $a[1.0] = 'bar';

        $this->assertSame(2, $a->length);
        $this->assertSame('["foo","bar"]', json_encode($a));

        $this->assertSame('foo', $a[0.0]);
        $this->assertSame('bar', $a[1]);
    }

    #[Test]
    public function invalidIndex()
    {
        $a = new ScriptArray();

        $a['foo'] = 'bar';
        $a[42] = 'baz';

        $this->assertSame(1, $a->length);
        $this->assertSame('{"42":"baz","foo":"bar"}', json_encode($a));

        unset($a['foo']);
        $this->assertSame(1, $a->length);
        $this->assertSame('{"42":"baz"}', json_encode($a));

        unset($a[42]);
        $this->assertSame(1, $a->length);
        $this->assertSame('{"42":null}', json_encode($a));
    }
}
