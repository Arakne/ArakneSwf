<?php

namespace Arakne\Tests\Swf\Extractor;

use Arakne\Swf\Extractor\MissingCharacter;
use Arakne\Swf\Extractor\Modifier\CharacterModifierInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MissingCharacterTest extends TestCase
{
    #[Test]
    public function modify()
    {
        $char = new MissingCharacter(1234);

        $this->assertSame($char, $char->modify($this->createMock(CharacterModifierInterface::class)));
    }
}
