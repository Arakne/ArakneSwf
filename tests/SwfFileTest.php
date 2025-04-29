<?php

namespace Arakne\Tests\Swf;

use Arakne\Swf\Avm\Api\ScriptArray;
use Arakne\Swf\Avm\Api\ScriptObject;
use Arakne\Swf\Parser\Error\ErrorCollector;
use Arakne\Swf\Parser\Error\TagParseError;
use Arakne\Swf\Parser\Error\TagParseErrorType;
use Arakne\Swf\Parser\Error\TagParseException;
use Arakne\Swf\Parser\Structure\SwfTagPosition;
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
        $this->assertEquals(new SwfTagPosition(9, 29, 3), $tags[0][0]);
        $this->assertInstanceOf(SetBackgroundColorTag::class, $tags[0][1]);
        $this->assertEquals(new SwfTagPosition(12, 38, 168338), $tags[1][0]);
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
        $swf = new SwfFile(__DIR__.'/Fixtures/1317.swf');

        foreach ($swf->tags() as $tag) {
            $this->assertIsObject($tag);
        }
    }

    #[Test]
    public function withExtraBytesCollectError()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/1317.swf', $errors = new ErrorCollector());

        foreach ($swf->tags() as $tag) {
            $this->assertIsObject($tag);
        }

        /** @var TagParseError[] $errors */
        $errors = iterator_to_array($errors);

        $this->assertCount(1, $errors);

        $this->assertSame(26, $errors[0]->position->type);
        $this->assertSame(5893, $errors[0]->position->offset);
        $this->assertSame(TagParseErrorType::ExtraBytes, $errors[0]->error);
        $this->assertSame([
            'length' => 8,
            'data' => "\xf6\xda\xb3\xb5\xd7\xfb\x31\xc0",
        ], $errors[0]->payload);
    }

    #[Test]
    public function withExtraBytesThrowError()
    {
        $swf = new SwfFile(__DIR__.'/Fixtures/1317.swf', new ErrorCollector(true));

        try {
            foreach ($swf->tags() as $tag) {
                $this->assertIsObject($tag);
            }

            $this->fail('Expected exception not thrown');
        } catch (TagParseException $e) {
            $error = $e->error;
            $this->assertStringStartsWith('Error parsing tag 26: ExtraBytes', $e->getMessage());
            $this->assertSame(26, $error->position->type);
            $this->assertSame(5893, $error->position->offset);
            $this->assertSame(TagParseErrorType::ExtraBytes, $error->error);
            $this->assertSame([
                'length' => 8,
                'data' => "\xf6\xda\xb3\xb5\xd7\xfb\x31\xc0",
            ], $error->payload);
        }
    }
}
