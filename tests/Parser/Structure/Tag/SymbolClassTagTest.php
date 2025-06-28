<?php

namespace Arakne\Tests\Swf\Parser\Structure\Tag;

use Arakne\Swf\Parser\Structure\Tag\SymbolClassTag;
use Arakne\Tests\Swf\Parser\ParserTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SymbolClassTagTest extends ParserTestCase
{
    #[Test]
    public function read()
    {
        $reader = $this->createReader(__DIR__.'/../../Fixtures/TestFlex.swf', 2275941);
        $tag = SymbolClassTag::read($reader);

        $this->assertSame([
            4 => 'mx.graphics.shaderClasses.ColorDodgeShader_ShaderClass',
            9 => 'mx.graphics.shaderClasses.LuminosityMaskShader_ShaderClass',
            2 => 'mx.graphics.shaderClasses.SaturationShader_ShaderClass',
            3 => 'mx.graphics.shaderClasses.SoftLightShader_ShaderClass',
            6 => 'mx.graphics.shaderClasses.ColorShader_ShaderClass',
            5 => 'mx.graphics.shaderClasses.ExclusionShader_ShaderClass',
            8 => 'mx.graphics.shaderClasses.ColorBurnShader_ShaderClass',
            1 => 'mx.graphics.shaderClasses.LuminosityShader_ShaderClass',
            7 => 'mx.graphics.shaderClasses.HueShader_ShaderClass',
            0 => 'TestFlex',
        ], $tag->symbols);
    }
}
