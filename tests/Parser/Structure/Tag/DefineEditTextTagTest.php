<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineEditTextTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;

class DefineEditTextTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../Fixtures/Examples1.swf', 4822);
        $tag = DefineEditTextTag::read($reader);

        $this->assertSame(19, $tag->characterId);
        $this->assertEquals(new Rectangle(
            xmin: -40,
            xmax: 1320,
            ymin: -40,
            ymax: 431
        ), $tag->bounds);
        $this->assertTrue($tag->border);
        $this->assertSame(280, $tag->fontHeight);
        $this->assertFalse($tag->wordWrap);
        $this->assertFalse($tag->multiline);
        $this->assertFalse($tag->password);
        $this->assertFalse($tag->readOnly);
        $this->assertFalse($tag->autoSize);
        $this->assertFalse($tag->noSelect);
        $this->assertFalse($tag->wasStatic);
        $this->assertFalse($tag->useOutlines);
        $this->assertSame(1, $tag->fontId);
        $this->assertEquals(new Color(0, 0, 0, 255), $tag->textColor);
        $this->assertSame(0, $tag->layout->align);
        $this->assertSame(0, $tag->layout->leftMargin);
        $this->assertSame(0, $tag->layout->rightMargin);
        $this->assertSame(0, $tag->layout->indent);
        $this->assertSame(40, $tag->layout->leading);
        $this->assertSame('', $tag->variableName);
    }
}
