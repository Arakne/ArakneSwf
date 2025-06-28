<?php

namespace Arakne\Tests\Swf;

use Arakne\Swf\Avm\Api\ScriptArray;
use Arakne\Swf\Avm\Api\ScriptObject;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Parser\Error\ParserExtraDataException;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\SwfTag;
use Arakne\Swf\Parser\Structure\Tag\DoActionTag;
use Arakne\Swf\Parser\Structure\Tag\EndTag;
use Arakne\Swf\Parser\Structure\Tag\FileAttributesTag;
use Arakne\Swf\Parser\Structure\Tag\SetBackgroundColorTag;
use Arakne\Swf\Parser\Structure\Tag\ShowFrameTag;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function array_map;
use function iterator_to_array;

class SwfFileTest extends TestCase
{
    public static function provideVariables()
    {
        return [
            'objects' => [
                __DIR__.'/Fixtures/objects.swf',
                [
                    'bag' => new ScriptObject([
                        'a' => 1,
                        'b' => false,
                    ]),
                    'arr' => new ScriptArray(1, 2),
                    'inlined_object' => new ScriptObject([
                        'd' => 'hello',
                        'c' => 1.3,
                    ]),
                    'inlined_array' => [1, 2, 3],
                    'get_member' => 1,
                    'array_access' => 2,
                    'get_member_str' => false,
                ],
            ],
            'simple' => [
                __DIR__.'/Fixtures/simple.swf',
                [
                    'simple_int' => 123,
                    'simple_string' => 'abc',
                    'simple_float' => 1.23,
                    'simple_bool' => true,
                    'simple_null' => null,
                ],
            ],
            'big' => [
                __DIR__.'/Fixtures/big.swf',
                [
                    'big_int' => 1234567890,
                    'negative_int' => -1234567890,
                    'big_float' => 1234567890123.1235,
                    'negative_float' => -1234567890123.1235,
                ],
            ],
            'cast' => [
                __DIR__.'/Fixtures/cast.swf',
                [
                    'str_to_number' => 1234.0,
                    'float_to_str' => '1234.5678',
                    'int_to_bool' => true,
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideVariables')]
    public function variable(string $file, array $expected)
    {
        $swf = new SwfFile($file);

        $this->assertEquals($expected, $swf->variables());
    }

    #[Test]
    public function execute()
    {
        $file = new SwfFile(__DIR__.'/Fixtures/lang_fr_801.swf');

        $state = $file->execute();
        $this->assertEmpty($state->stack);
        $this->assertCount(1701, $state->constants);

        $this->assertSame(801, $state->variables['VERSION']);
        $this->assertSame('Menu du chat', $state->variables['CHAT_MENU']);
        $this->assertSame('FR,0', $state->variables['C']['DEFAULT_COMMUNITY']);
        $this->assertSame(180000.0, $state->variables['C']['DELAY_RECO_START']);

        $this->assertSame('44fada8d52329bcd9dddb9259c305897', md5(json_encode($state->variables)));
    }

    #[Test]
    public function tags()
    {
        $file = new SwfFile(__DIR__.'/Fixtures/lang_fr_801.swf');

        $tags = iterator_to_array($file->tags(), false);
        $this->assertCount(5, $tags);
        $this->assertInstanceOf(FileAttributesTag::class, $tags[0]);
        $this->assertInstanceOf(SetBackgroundColorTag::class, $tags[1]);
        $this->assertInstanceOf(DoActionTag::class, $tags[2]);
        $this->assertInstanceOf(ShowFrameTag::class, $tags[3]);
        $this->assertInstanceOf(EndTag::class, $tags[4]);

        $tags = iterator_to_array($file->tags(12), false);
        $this->assertCount(1, $tags);
        $this->assertInstanceOf(DoActionTag::class, $tags[0]);

        $tags = iterator_to_array($file->tags(12, 9), false);
        $this->assertCount(2, $tags);
        $this->assertInstanceOf(SetBackgroundColorTag::class, $tags[0]);
        $this->assertInstanceOf(DoActionTag::class, $tags[1]);

        $tags = [];

        foreach($file->tags(12, 9) as $pos => $tag) {
            $tags[] = [$pos, $tag];
        }

        $this->assertCount(2, $tags);
        $this->assertEquals(new SwfTag(9, 29, 3), $tags[0][0]);
        $this->assertInstanceOf(SetBackgroundColorTag::class, $tags[0][1]);
        $this->assertEquals(new SwfTag(12, 38, 168338), $tags[1][0]);
        $this->assertInstanceOf(DoActionTag::class, $tags[1][1]);
    }

    #[Test]
    public function valid()
    {
        $this->assertTrue((new SwfFile(__DIR__.'/Fixtures/lang_fr_801.swf'))->valid());
        $this->assertFalse((new SwfFile(__DIR__.'/Fixtures/simple.sc'))->valid());
        $this->assertFalse((new SwfFile(__DIR__.'/Fixtures/invalid-signature'))->valid());
        $this->assertFalse((new SwfFile(__DIR__.'/Fixtures/invalid-too-small'))->valid());
        $this->assertFalse((new SwfFile(__DIR__.'/Fixtures/invalid-version-too-high'))->valid());
        $this->assertFalse((new SwfFile(__DIR__.'/Fixtures/invalid-length-too-high'))->valid());
    }

    /**
     * Simply parse SWF files to check for exceptions
     *
     * Some test files are from https://condor.depaul.edu/sjost/hci430/flash-examples.htm
     */
    #[Test]
    #[TestWith([__DIR__.'/Fixtures/Examples1.swf'])]
    #[TestWith([__DIR__.'/Fixtures/sunAndShadow.swf'])]
    public function coverage(string $file)
    {
        $swf = new SwfFile($file);

        foreach ($swf->tags() as $tag) {
            $this->assertIsObject($tag);
        }
    }

    #[Test]
    public function withExtraBytesIgnoreError()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/1317.swf', errors: 0);

        foreach ($swf->tags() as $tag) {
            $this->assertIsObject($tag);
        }
    }

    #[Test]
    public function withExtraBytesThrowError()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/1317.swf');

        try {
            foreach ($swf->tags() as $tag) {
                $this->assertIsObject($tag);
            }

            $this->fail('Expected exception not thrown');
        } catch (ParserExtraDataException $e) {
            $this->assertStringStartsWith('Extra data found after tag 26 at offset 37582 (length = 8)', $e->getMessage());
            $this->assertSame(37582, $e->offset);
            $this->assertSame(8, $e->length);
        }
    }

    #[Test]
    public function assetByName()
    {
        $swf = new SwfFile(__DIR__.'/Extractor/Fixtures/1047/1047.swf');

        $staticR = $swf->assetByName('staticR');
        $this->assertInstanceOf(SpriteDefinition::class, $staticR);
        $this->assertSame(66, $staticR->id);
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Extractor/Fixtures/1047/staticR.svg', $staticR->toSvg());
    }

    #[Test]
    public function assetById()
    {
        $swf = new SwfFile(__DIR__.'/Extractor/Fixtures/complex_sprite.swf');
        $sprite = $swf->assetById(13);

        $this->assertInstanceOf(SpriteDefinition::class, $sprite);
        $this->assertSame(13, $sprite->id);
        $this->assertXmlStringEqualsXmlFile(__DIR__.'/Extractor/Fixtures/sprite-13.svg', $sprite->toSvg());
    }

    #[Test]
    public function exportedAssets()
    {
        $swf = new SwfFile(__DIR__.'/Extractor/Fixtures/1047/1047.swf');

        $exported = $swf->exportedAssets();

        $this->assertContainsOnly(SpriteDefinition::class, $exported);
        $this->assertSame([
            'runR' => 29,
            'runL' => 43,
            'bonusR' => 53,
            'bonusL' => 56,
            'anim0R' => 62,
            'anim0L' => 64,
            'staticR' => 66,
            'staticL' => 68,
            'walkL' => 70,
            'walkR' => 72,
            'anim1R' => 77,
            'anim1L' => 79,
            'hitR' => 91,
            'hitL' => 95,
            'dieR' => 97,
            'dieL' => 99,
        ], array_map(fn (SpriteDefinition $sprite) => $sprite->id, $exported));
    }

    #[Test]
    public function timeline()
    {
        $swf = new SwfFile(__DIR__.'/Extractor/Fixtures/1/1.swf');
        $timeline = $swf->timeline(false);

        foreach ($timeline->toSvgAll() as $f => $svg) {
            $this->assertXmlStringEqualsXmlFile(__DIR__.'/Extractor/Fixtures/1/frame_'.$f.'.svg', $svg);
        }
    }

    #[Test]
    public function header()
    {
        $swf = new SwfFile(__DIR__.'/Extractor/Fixtures/1/1.swf');
        $header = $swf->header();

        $this->assertSame('CWS', $header->signature);
        $this->assertSame(7, $header->version);
        $this->assertSame(2564, $header->fileLength);
        $this->assertEquals(new Rectangle(0, 20, 0, 20), $header->frameSize);
        $this->assertSame(12.0, $header->frameRate);
        $this->assertSame(1, $header->frameCount);
    }

    #[Test]
    public function frameRate()
    {
        $swf = new SwfFile(__DIR__.'/Extractor/Fixtures/1/1.swf');
        $this->assertSame(12, $swf->frameRate());
    }
}
