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
        ], $this->exec('--frames 5 --frames all file.swf /tmp'));
    }

    private function exec(string $args): array
    {
        return json_decode(shell_exec('php ' . __DIR__.'/Fixture/extract-options-test.php ' . $args), true);
    }
}
