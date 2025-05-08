<?php

namespace Arakne\Tests\Swf\Parser\Structure\Record;

use Arakne\Swf\Parser\Structure\Record\Color;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ColorTransformTest extends TestCase
{
    #[Test]
    public function identityTransform(): void
    {
        $transform = new ColorTransform();

        $color = new Color(255, 128, 64, 255);
        $result = $transform->transform($color);

        $this->assertEquals($color, $result);
    }

    #[Test]
    public function addTransform(): void
    {
        $transform = new ColorTransform(
            redAdd: 50,
            greenAdd: 25,
            blueAdd: 50
        );

        $color = new Color(100, 150, 200, 255);
        $expected = new Color(150, 175, 250, 255);

        $result = $transform->transform($color);
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function multiplyTransform(): void
    {
        $transform = new ColorTransform(
            redMult: 128,  // 0.5
            greenMult: 384, // 1.5
            blueMult: 192   // 0.75
        );

        $color = new Color(200, 100, 80, 255);
        $expected = new Color(100, 150, 60, 255);

        $result = $transform->transform($color);
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function combinedTransform(): void
    {
        $transform = new ColorTransform(
            redMult: 205,  // ~0.8
            greenMult: 307, // ~1.2
            blueMult: 128,  // 0.5
            redAdd: 10,
            greenAdd: 5,
            blueAdd: 5
        );

        $color = new Color(100, 100, 100, 255);
        $expected = new Color(90, 124, 55, 255);

        $result = $transform->transform($color);
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function upperBoundary(): void
    {
        $transform = new ColorTransform(
            redAdd: 100,
            greenAdd: 100,
            blueAdd: 100,
            alphaAdd: 100
        );

        $color = new Color(200, 200, 200, 200);
        $expected = new Color(255, 255, 255, 255);

        $result = $transform->transform($color);
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function lowerBoundary(): void
    {
        $transform = new ColorTransform(
            redMult: 128,
            greenMult: 128,
            blueMult: 128,
            alphaMult: 128,
            redAdd: -100,
            greenAdd: -100,
            blueAdd: -100,
            alphaAdd: -100
        );

        $color = new Color(100, 100, 100, 200);
        $expected = new Color(0, 0, 0, 0);

        $result = $transform->transform($color);
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function alphaTransformation(): void
    {
        $transform = new ColorTransform(
            alphaMult: 128,
            alphaAdd: 50
        );

        $color = new Color(100, 100, 100, 200);
        $expected = new Color(100, 100, 100, 150);

        $result = $transform->transform($color);
        $this->assertEquals($expected, $result);
    }
}
