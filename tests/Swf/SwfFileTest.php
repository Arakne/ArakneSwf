<?php

namespace Arakne\Tests\Swf;

use Arakne\Swf\Avm\Api\ScriptArray;
use Arakne\Swf\Avm\Api\ScriptObject;
use Arakne\Swf\Parser\Structure\Tag\DoActionTag;
use Arakne\Swf\Parser\Structure\Tag\EndTag;
use Arakne\Swf\Parser\Structure\Tag\FileAttributesTag;
use Arakne\Swf\Parser\Structure\Tag\SetBackgroundColorTag;
use Arakne\Swf\Parser\Structure\Tag\ShowFrameTag;
use Arakne\Swf\Parser\Swf;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

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

        $tags = iterator_to_array($file->tags());
        $this->assertCount(5, $tags);
        $this->assertInstanceOf(FileAttributesTag::class, $tags[0]);
        $this->assertInstanceOf(SetBackgroundColorTag::class, $tags[1]);
        $this->assertInstanceOf(DoActionTag::class, $tags[2]);
        $this->assertInstanceOf(ShowFrameTag::class, $tags[3]);
        $this->assertInstanceOf(EndTag::class, $tags[4]);

        $tags = iterator_to_array($file->tags(12));
        $this->assertCount(1, $tags);
        $this->assertInstanceOf(DoActionTag::class, $tags[0]);

        $tags = iterator_to_array($file->tags(12, 9));
        $this->assertCount(2, $tags);
        $this->assertInstanceOf(SetBackgroundColorTag::class, $tags[0]);
        $this->assertInstanceOf(DoActionTag::class, $tags[1]);
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
}
