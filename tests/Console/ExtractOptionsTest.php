<?php

namespace Arakne\Tests\Swf\Console;

use Arakne\Swf\Console\ExtractOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function shell_exec;
use function var_dump;

class ExtractOptionsTest extends TestCase
{
    #[Test]
    public function default()
    {
        $this->assertEquals([
            'command' => __DIR__.'/Fixture/extract-options-test.php',
            'error' => null,
            'help' => true,
            'files' => [],
            'output' => '',
            'outputFilename' => ExtractOptions::DEFAULT_OUTPUT_FILENAME,
            'characters' => [],
            'exported' => [],
            'frames' => null,
            'fullAnimation' => false,
            'allSprites' => false,
            'allExported' => false,
            'timeline' => false,
            'variables' => false,
            'frameFormat' => [['format' => 'svg', 'size' => null, 'options' => []]],
            'animationFormat' => [],
        ], $this->exec(''));
    }

    #[Test]
    public function missingOutput()
    {
        $this->assertEquals([
            'command' => __DIR__.'/Fixture/extract-options-test.php',
            'error' => 'Not enough arguments: <file> and <output> are required',
            'help' => false,
            'files' => [],
            'output' => '',
            'outputFilename' => ExtractOptions::DEFAULT_OUTPUT_FILENAME,
            'characters' => [],
            'exported' => [],
            'frames' => null,
            'fullAnimation' => false,
            'allSprites' => false,
            'allExported' => false,
            'timeline' => false,
            'variables' => false,
            'frameFormat' => [['format' => 'svg', 'size' => null, 'options' => []]],
            'animationFormat' => [],
        ], $this->exec('file.swf'));
    }

    #[Test]
    public function minimal()
    {
        $this->assertEquals([
            'command' => __DIR__.'/Fixture/extract-options-test.php',
            'error' => null,
            'help' => false,
            'files' => ['file.swf'],
            'output' => '/tmp',
            'outputFilename' => ExtractOptions::DEFAULT_OUTPUT_FILENAME,
            'characters' => [],
            'exported' => [],
            'frames' => null,
            'fullAnimation' => false,
            'allSprites' => false,
            'allExported' => false,
            'timeline' => false,
            'variables' => false,
            'frameFormat' => [['format' => 'svg', 'size' => null, 'options' => []]],
            'animationFormat' => [],
        ], $this->exec('file.swf /tmp'));
    }

    #[
        Test,
        TestWith(['-h', 'help']),
        TestWith(['--help', 'help']),
        TestWith(['--full-animation', 'fullAnimation']),
        TestWith(['--all-sprites', 'allSprites']),
        TestWith(['--all-exported', 'allExported']),
        TestWith(['--timeline', 'timeline']),
        TestWith(['--variables', 'variables']),
    ]
    public function flagsOptions(string $cliOption, string $optionName)
    {
        $this->assertEquals([
            'command' => __DIR__.'/Fixture/extract-options-test.php',
            'error' => null,
            'help' => false,
            'files' => ['file.swf'],
            'output' => '/tmp',
            'outputFilename' => ExtractOptions::DEFAULT_OUTPUT_FILENAME,
            'characters' => [],
            'exported' => [],
            'frames' => null,
            'fullAnimation' => false,
            'allSprites' => false,
            'allExported' => false,
            'timeline' => false,
            'variables' => false,
            'frameFormat' => [['format' => 'svg', 'size' => null, 'options' => []]],
            'animationFormat' => [],
            $optionName => true,
        ], $this->exec($cliOption . ' file.swf /tmp'));
    }

    #[Test]
    public function outputFilename()
    {
        $this->assertEquals([
            'command' => __DIR__.'/Fixture/extract-options-test.php',
            'error' => null,
            'help' => false,
            'files' => ['file.swf'],
            'output' => '/tmp',
            'outputFilename' => '{basename}.foo',
            'characters' => [],
            'exported' => [],
            'frames' => null,
            'fullAnimation' => false,
            'allSprites' => false,
            'allExported' => false,
            'timeline' => false,
            'variables' => false,
            'frameFormat' => [['format' => 'svg', 'size' => null, 'options' => []]],
            'animationFormat' => [],
        ], $this->exec('--output-filename {basename}.foo file.swf /tmp'));
    }

    #[Test]
    public function characters()
    {
        $this->assertEquals([
            'command' => __DIR__.'/Fixture/extract-options-test.php',
            'error' => null,
            'help' => false,
            'files' => ['file.swf'],
            'output' => '/tmp',
            'outputFilename' => ExtractOptions::DEFAULT_OUTPUT_FILENAME,
            'characters' => [42, 21, 666],
            'exported' => [],
            'frames' => null,
            'fullAnimation' => false,
            'allSprites' => false,
            'allExported' => false,
            'timeline' => false,
            'variables' => false,
            'frameFormat' => [['format' => 'svg', 'size' => null, 'options' => []]],
            'animationFormat' => [],
        ], $this->exec('-c 42 -c 21 --character 666 file.swf /tmp'));
    }

    #[Test]
    public function exported()
    {
        $this->assertEquals([
            'command' => __DIR__.'/Fixture/extract-options-test.php',
            'error' => null,
            'help' => false,
            'files' => ['file.swf'],
            'output' => '/tmp',
            'outputFilename' => ExtractOptions::DEFAULT_OUTPUT_FILENAME,
            'characters' => [],
            'exported' => ['Foo', 'Bar', 'Baz'],
            'frames' => null,
            'fullAnimation' => false,
            'allSprites' => false,
            'allExported' => false,
            'timeline' => false,
            'variables' => false,
            'frameFormat' => [['format' => 'svg', 'size' => null, 'options' => []]],
            'animationFormat' => [],
        ], $this->exec('-e Foo -e Bar --exported Baz file.swf /tmp'));
    }

    #[Test]
    public function frames()
    {
        $this->assertEquals([
            'command' => __DIR__.'/Fixture/extract-options-test.php',
            'error' => null,
            'help' => false,
            'files' => ['file.swf'],
            'output' => '/tmp',
            'outputFilename' => ExtractOptions::DEFAULT_OUTPUT_FILENAME,
            'characters' => [],
            'exported' => [],
            'frames' => [5, 6, 10, 11, 12, 13, 14, 15],
            'fullAnimation' => false,
            'allSprites' => false,
            'allExported' => false,
            'timeline' => false,
            'variables' => false,
            'frameFormat' => [['format' => 'svg', 'size' => null, 'options' => []]],
            'animationFormat' => [],
        ], $this->exec('--frames 5 --frames 6 --frames 10-15 file.swf /tmp'));

        $this->assertEquals([
            'command' => __DIR__.'/Fixture/extract-options-test.php',
            'error' => null,
            'help' => false,
            'files' => ['file.swf'],
            'output' => '/tmp',
            'outputFilename' => ExtractOptions::DEFAULT_OUTPUT_FILENAME,
            'characters' => [],
            'exported' => [],
            'frames' => null,
            'fullAnimation' => false,
            'allSprites' => false,
            'allExported' => false,
            'timeline' => false,
            'variables' => false,
            'frameFormat' => [['format' => 'svg', 'size' => null, 'options' => []]],
            'animationFormat' => [],
        ], $this->exec('--frames 5 --frames all file.swf /tmp'));
    }

    #[
        Test,
        TestWith(['--frame-format png', [['format' => 'png', 'size' => null, 'options' => []]]]),
        TestWith(['--frame-format gif', [['format' => 'gif', 'size' => null, 'options' => []]]]),
        TestWith(['--frame-format webp@128', [['format' => 'webp', 'size' => ['width' => 128, 'height' => 128], 'options' => []]]]),
        TestWith(['--frame-format webp@100x200', [['format' => 'webp', 'size' => ['width' => 100, 'height' => 200], 'options' => []]]]),
        TestWith(['--frame-format svg --frame-format png@128', [['format' => 'svg', 'size' => null, 'options' => []], ['format' => 'png', 'size' => ['width' => 128, 'height' => 128], 'options' => []]]]),
        TestWith([
            '--frame-format a:gif@128',
            [],
            [['format' => 'gif', 'size' => ['width' => 128, 'height' => 128], 'options' => ['a' => true]]],
        ]),
        TestWith([
            '--frame-format a:gif@128 --frame-format png',
            [['format' => 'png', 'size' => null, 'options' => []]],
            [['format' => 'gif', 'size' => ['width' => 128, 'height' => 128], 'options' => ['a' => true]]],
        ]),
        TestWith([
            '--frame-format anim:webp',
            [],
            [['format' => 'webp', 'size' => null, 'options' => ['anim' => true]]],
        ]),
        TestWith([
            '--frame-format animation:webp',
            [],
            [['format' => 'webp', 'size' => null, 'options' => ['animation' => true]]],
        ]),
        TestWith([
            '--frame-format animated:webp',
            [],
            [['format' => 'webp', 'size' => null, 'options' => ['animated' => true]]],
        ]),
    ]
    public function frameFormatSuccess(string $cliOption, array $expected, array $expectedAnim = [])
    {
        $this->assertEquals([
            'command' => __DIR__.'/Fixture/extract-options-test.php',
            'error' => null,
            'help' => false,
            'files' => ['file.swf'],
            'output' => '/tmp',
            'outputFilename' => ExtractOptions::DEFAULT_OUTPUT_FILENAME,
            'characters' => [],
            'exported' => [],
            'frames' => null,
            'fullAnimation' => false,
            'allSprites' => false,
            'allExported' => false,
            'timeline' => false,
            'variables' => false,
            'frameFormat' => $expected,
            'animationFormat' => $expectedAnim,
        ], $this->exec($cliOption . ' file.swf /tmp'));
    }

    #[
        Test,
        TestWith(['--frame-format invalid', 'the format invalid is not supported']),
    ]
    public function frameFormatInvalid(string $cliOption, string $error)
    {
        $this->assertEquals([
            'command' => __DIR__.'/Fixture/extract-options-test.php',
            'error' => 'Invalid value for option --frame-format: '.$error,
            'help' => false,
            'files' => [],
            'output' => '',
            'outputFilename' => ExtractOptions::DEFAULT_OUTPUT_FILENAME,
            'characters' => [],
            'exported' => [],
            'frames' => null,
            'fullAnimation' => false,
            'allSprites' => false,
            'allExported' => false,
            'timeline' => false,
            'variables' => false,
            'frameFormat' => [['format' => 'svg', 'size' => null, 'options' => []]],
            'animationFormat' => [],
        ], $this->exec($cliOption . ' file.swf /tmp'));
    }

    private function exec(string $args): array
    {
        return json_decode(shell_exec('php ' . __DIR__.'/Fixture/extract-options-test.php ' . $args), true);
    }
}
