<?php

namespace Arakne\Tests\Swf\Extractor\Modifier;

use Arakne\Swf\Extractor\Modifier\GotoAndStop;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GotoAndStopTest extends TestCase
{
    #[Test]
    public function withLabel()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $sprite = $extractor->byName('staticR');
        $newSprite = $sprite->modify(new GotoAndStop('static'));

        $this->assertSame(1, $newSprite->framesCount(true));
        $this->assertSame('static', $newSprite->timeline()->frames[0]->objects[1]->object->timeline()->frames[0]->label);
    }

    #[Test]
    public function withNumber()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $sprite = $extractor->byName('staticR');
        $newSprite = $sprite->modify(new GotoAndStop(3));

        $this->assertSame(1, $newSprite->framesCount(true));
        $this->assertXmlStringEqualsXmlFile(
            __DIR__.'/../Fixtures/1047/staticR-2.svg',
            $newSprite->toSvg(),
        );
    }
}
