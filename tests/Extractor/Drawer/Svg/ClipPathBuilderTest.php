<?php

namespace Arakne\Tests\Swf\Extractor\Drawer\Svg;

use Arakne\Swf\Extractor\Drawer\Svg\ClipPathBuilder;
use Arakne\Swf\Extractor\Drawer\Svg\SvgBuilder;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class ClipPathBuilderTest extends TestCase
{
    private SimpleXMLElement $root;
    private ClipPathBuilder $builder;

    protected function setUp(): void
    {
        $this->root = new SimpleXMLElement('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $this->builder = new ClipPathBuilder(
            $this->root->addChild('clipPath'),
            new SvgBuilder($this->root)
        );
    }

    #[Test]
    public function shouldDoNothingOnUnsupportedCharacters()
    {
        $swf = new SwfFile(__DIR__.'/../../Fixtures/mob-leponge/mob-leponge.swf');
        $extractor = new SwfExtractor($swf);

        $extractor->character(1)->draw($this->builder); // Image

        $this->builder->startClip($extractor->character(1), new Matrix(), 0);
        $this->builder->endClip('');

        $this->assertNull($this->builder->render());
        $this->assertCount(1, (array) $this->root->children());
        $this->assertEmpty((array) $this->root->clipPath->children());
    }

    #[Test]
    public function buildWithSprite()
    {
        $swf = new SwfFile(__DIR__.'/../../Fixtures/mob-leponge/mob-leponge.swf');
        $extractor = new SwfExtractor($swf);

        $extractor->character(4)->draw($this->builder); // Sprite

        $this->builder->startClip($extractor->character(1), new Matrix(), 0);
        $this->builder->endClip('');

        $expected = <<<'XML'
        <?xml version="1.0"?>
        <svg xmlns="http://www.w3.org/2000/svg">
            <clipPath>
                <path fill-rule="evenodd" fill="url(#gradient-R63dfb90eb595e9795bdd21b4fefc7c4b)" stroke="none" d="M5.15 0Q5.15 2.15 3.65 3.65Q2.15 5.15 0 5.15Q-2.15 5.15 -3.65 3.65Q-5.15 2.15 -5.15 0Q-5.15 -2.15 -3.65 -3.65Q-2.15 -5.15 0 -5.15Q2.15 -5.15 3.65 -3.65Q5.15 -2.15 5.15 0" transform="matrix(1, 0, 0, 1, -5.15, -5.15) translate(5.15,5.15)"/>
            </clipPath>
            <radialGradient gradientTransform="matrix(0.0068, 0, 0, 0.0068, 0, 0)" gradientUnits="userSpaceOnUse" spreadMethod="pad" id="gradient-R63dfb90eb595e9795bdd21b4fefc7c4b" cx="0" cy="0" r="819.2">
                <stop offset="0" stop-color="#99795a"/>
                <stop offset="0.44705882352941" stop-color="#99734f" stop-opacity="0.43137254901961"/>
                <stop offset="0.83137254901961" stop-color="#9c6e44" stop-opacity="0"/>
            </radialGradient>
        </svg>
        XML;

        $this->assertNull($this->builder->render());
        $this->assertXmlStringEqualsXmlString($expected, $this->root->asXML());
    }

    #[Test]
    public function buildClipPathWithShape()
    {
        $swf = new SwfFile(__DIR__.'/../../Fixtures/mob-leponge/mob-leponge.swf');
        $extractor = new SwfExtractor($swf);
        $extractor->character(12)->draw($this->builder); // Shape

        $actual = $this->root->asXML();
        $expected = <<<'XML'
        <?xml version="1.0"?>
        <svg xmlns="http://www.w3.org/2000/svg">
            <clipPath>
                <path fill-rule="evenodd" fill="#704d00" stroke="none" d="M1.25 -1.05L4.3 -0.05L4.7 1.6L2.55 2.45L-0.3 1.85L-1.45 0.75L-1.05 -0.5L1.25 -1.05" transform="translate(1.45,1.05)"/><path fill="none" stroke="#000000" stroke-opacity="0.30196078431373" stroke-width="0.05" stroke-linecap="round" stroke-linejoin="round" d="M1.25 -1.05L-1.05 -0.5L-1.45 0.75L-0.3 1.85L2.55 2.45L4.7 1.6L4.3 -0.05L1.25 -1.05" transform="translate(1.45,1.05)"/>
            </clipPath>
        </svg>
        XML;

        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }
}
