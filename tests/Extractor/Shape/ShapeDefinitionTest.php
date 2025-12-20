<?php

namespace Arakne\Tests\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Modifier\CharacterModifierInterface;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ShapeDefinitionTest extends TestCase
{
    #[Test]
    public function modify()
    {
        $shape = new SwfFile(__DIR__ . '/../Fixtures/2.swf')->assetById(1);
        $modifier = $this->createMock(CharacterModifierInterface::class);
        $newShape = clone $shape;

        $modifier->expects($this->once())->method('applyOnShape')->with($shape)->willReturn($newShape);

        $this->assertSame($newShape, $shape->modify($modifier));
    }
}
