<?php

namespace Arakne\Tests\Swf\Extractor\Modifier;

use Arakne\Swf\Extractor\Modifier\AbstractCharacterModifier;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractCharacterModifierTest extends TestCase
{
    #[Test]
    public function methods()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/1047/1047.swf');
        $extractor = new SwfExtractor($swf);

        $modifier = new class extends AbstractCharacterModifier {};

        $shape = $extractor->character(36);
        $this->assertSame($shape, $modifier->applyOnShape($shape));

        $sprite = $extractor->character(65);
        $this->assertSame($sprite, $modifier->applyOnSprite($sprite));

        $timeline = $extractor->character(65)->timeline();
        $this->assertSame($timeline, $modifier->applyOnTimeline($timeline));

        $frame = $timeline->frames[0];
        $this->assertSame($frame, $modifier->applyOnFrame($frame));

        $image = $extractor->character(1);
        $this->assertSame($image, $modifier->applyOnImage($image));

        $swf = new SwfFile(__DIR__.'/../Fixtures/morphshape/morphshape.swf');
        $morphShape = $swf->assetById(1);
        $this->assertSame($morphShape, $modifier->applyOnMorphShape($morphShape));
    }
}
