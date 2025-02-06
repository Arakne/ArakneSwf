<?php

namespace Arakne\Tests\Swf\Extractor\Shape;

use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\Shape\ShapeProcessor;
use Arakne\Swf\Extractor\Shape\Svg\SvgCanvas;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Tag\DefineShape4Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function chmod;
use function file_put_contents;

class ShapeToSvgFunctionalTest extends TestCase
{
    #[
        Test,
        TestWith(['shape.swf', 'shape.svg']),
        TestWith(['2.swf', '2.svg']),
    ]
    public function singleShape(string $swf, string $svg): void
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/' . $swf);
        $processor = new ShapeProcessor();

        /** @var DefineShapeTag|DefineShape4Tag $tag */
        foreach ($swf->tags(2, 22, 32, 83) as $tag) {
            $shape = $processor->process($tag);
            $canvas = new SvgCanvas($tag->shapeBounds);
            $canvas->shape($shape);

            $shape = $canvas->toXml();
            break;
        }

        $this->assertSvg(__DIR__.'/../Fixtures/' . $svg, $shape);
    }

    #[Test]
    public function withTransparency()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/complex_sprite.swf');
        $shape = (new SwfExtractor($swf))->character(11);

        $this->assertSvg(__DIR__.'/../Fixtures/shape_with_transparency.svg', $shape->toSvg());
    }

    #[Test]
    public function withRadialGradient()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/complex_sprite.swf');
        $shape = (new SwfExtractor($swf))->character(1);

        $this->assertSvg(__DIR__.'/../Fixtures/shape_with_radial_gradient.svg', $shape->toSvg());
    }

    #[Test]
    public function withComplexFill()
    {
        $swf = new SwfFile(__DIR__.'/../Fixtures/complex_sprite.swf');
        $shape = (new SwfExtractor($swf))->character(8);

        $this->assertSvg(__DIR__.'/../Fixtures/shape_with_complex_fill.svg', $shape->toSvg());
    }

    private function assertSvg(string $expectedFile, string|ShapeDefinition $shape)
    {
        if ($shape instanceof ShapeDefinition) {
            $shape = $shape->toSvg();
        }

        try {
            $this->assertXmlStringEqualsXmlFile($expectedFile, $shape);
        } catch (\Exception $e) {
            file_put_contents(__DIR__.'/../Fixtures/test.svg', $shape);
            chmod(__DIR__.'/../Fixtures/test.svg', 0666);
            throw $e;
        }
    }
}
