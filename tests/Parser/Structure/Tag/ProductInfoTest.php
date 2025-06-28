<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\ProductInfo;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProductInfoTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../Fixtures/TestFlex.swf', 536);
        $tag = ProductInfo::read($reader);

        $this->assertSame(3, $tag->productId);
        $this->assertSame(6, $tag->edition);
        $this->assertSame(4, $tag->majorVersion);
        $this->assertSame(6, $tag->minorVersion);
        $this->assertSame(23201, $tag->buildNumber);
        $this->assertSame(1452749888546, $tag->compilationDate);
    }
}
