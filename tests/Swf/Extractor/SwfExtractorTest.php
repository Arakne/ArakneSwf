<?php

namespace Arakne\Tests\Swf\Extractor;

use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Tag\DefineShapeTag;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function chmod;
use function file_put_contents;

class SwfExtractorTest extends TestCase
{
    #[Test]
    public function shapes()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/2.swf'));
        $shapes = $extractor->shapes();

        $this->assertCount(2, $shapes);
        $this->assertSame([1, 2], array_keys($shapes));
        $this->assertContainsOnly(ShapeDefinition::class, $shapes);

        $this->assertSame(1, $shapes[1]->id);
        $this->assertInstanceOf(DefineShapeTag::class, $shapes[1]->tag);
        $this->assertSame(2, $shapes[2]->id);
        $this->assertInstanceOf(DefineShapeTag::class, $shapes[2]->tag);

        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/2.svg', $shapes[1]->toSvg());
        $this->assertXmlStringEqualsXmlString(
            <<<'SVG'
            <?xml version="1.0"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="25.3px" height="4.8px">
                <g transform="matrix(1.0, 0.0, 0.0, 1, -0.05, 0)">
                    <path fill="#000000" stroke="none" fill-rule="evenodd" d="M25.35 2.4Q25.3 3.4 21.6 4.1L12.7 4.8L3.75 4.1Q0 3.4 0.05 2.4Q0 1.4 3.75 0.7L12.7 0L21.6 0.7Q25.3 1.4 25.35 2.4"/>
                </g>
            </svg>
            SVG,
            $shapes[2]->toSvg()
        );

        $this->assertSame($shapes[1]->shape, $shapes[1]->shape);
        $this->assertSame($shapes[2]->shape, $shapes[2]->shape);
    }

    #[Test]
    public function sprites()
    {
        $extractor = new SwfExtractor(new SwfFile(__DIR__.'/Fixtures/complex_sprite.swf'));
        $sprites = $extractor->sprites();

        foreach ($sprites as $sprite) {
            file_put_contents(__DIR__.'/Fixtures/test.svg', $sprite->toSvg());
            chmod(__DIR__.'/Fixtures/test.svg', 0666);
            $this->assertXmlStringEqualsXmlFile(__DIR__.'/Fixtures/sprite-'.$sprite->id.'.svg', $sprite->toSvg());
        }
    }
}
