<?php

namespace Arakne\Tests\Swf\Util;

use Arakne\Swf\Util\Memory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;
use function var_dump;

class MemoryTest extends TestCase
{
    #[Test]
    public function max()
    {
        $this->assertGreaterThan(50_000_000, Memory::max());
        $this->assertSame(Memory::max(), Memory::max());
    }

    #[Test]
    public function current()
    {
        $this->assertGreaterThan(1_000_000, $current = Memory::current());
        $s = str_repeat('a', 40_000_000);
        $this->assertGreaterThan($current, Memory::current());
    }

    #[Test]
    public function usage()
    {
        $this->assertGreaterThan(0.0, Memory::usage());
        $this->assertLessThan(1.0, Memory::usage());
    }

    #[Test]
    public function parse()
    {
        $this->assertSame(PHP_INT_MAX, Memory::parse(''));
        $this->assertSame(PHP_INT_MAX, Memory::parse(' '));
        $this->assertSame(PHP_INT_MAX, Memory::parse('0'));
        $this->assertSame(PHP_INT_MAX, Memory::parse('-1'));
        $this->assertSame(PHP_INT_MAX, Memory::parse('0.0'));
        $this->assertSame(PHP_INT_MAX, Memory::parse('foo'));

        $this->assertSame(1234, Memory::parse(' 1234 '));
        $this->assertSame(1263616, Memory::parse(' 1234k'));
        $this->assertSame(134217728, Memory::parse('128M'));
        $this->assertSame(2147483648, Memory::parse('2G'));
    }
}
