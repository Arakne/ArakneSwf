<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\CSMTextSettingsTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function var_dump;

class CSMTextSettingsTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../../Extractor/Fixtures/core/core.swf', 801399);
        $tag = CSMTextSettingsTag::read($reader);

        $this->assertSame(536, $tag->textId);
        $this->assertSame(1, $tag->useFlashType);
        $this->assertSame(2, $tag->gridFit);
        $this->assertSame(0.0, $tag->thickness);
        $this->assertSame(0.0, $tag->sharpness);
    }
}
