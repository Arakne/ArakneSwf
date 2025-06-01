<?php

namespace Arakne\Tests\Swf\Console;

use Arakne\Swf\Console\ExtractCommand;
use Arakne\Swf\Console\ExtractOptions;
use Arakne\Swf\Extractor\Drawer\Converter\AnimationFormater;
use Arakne\Swf\Extractor\Drawer\Converter\DrawableFormater;
use Arakne\Swf\Extractor\Drawer\Converter\ImageFormat;
use Arakne\Tests\Swf\Extractor\ImageTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Vtiful\Kernel\Format;

use function array_diff;
use function array_values;
use function file_get_contents;
use function glob;
use function is_dir;
use function natsort;
use function ob_start;
use function scandir;
use function var_dump;

class ExtractCommandTest extends ImageTestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = '/tmp/'.uniqid('swf-extract-', true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->outputDir)) {
            return;
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->outputDir), RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($it as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
    }

    #[Test]
    public function help()
    {
        $output = $this->exec(new ExtractOptions(help: true));

        $this->assertStringContainsString('Extract resources from an SWF file.', $output);
        $this->assertStringContainsString('Usage:', $output);
    }

    #[Test]
    public function error()
    {
        $output = $this->exec(new ExtractOptions(error: 'My error'));

        $this->assertStringContainsString('Error: My error', $output);
        $this->assertStringContainsString('Extract resources from an SWF file.', $output);
    }

    #[Test]
    public function invalidSwfFile()
    {
        $output = $this->exec(new ExtractOptions(files: [__FILE__], output: $this->outputDir));

        $this->assertStringContainsString('[1/1] Processing file: ' . __FILE__ . ' error: The file ' . __FILE__ . ' is not a valid SWF file', $output);
    }

    #[Test]
    public function characters()
    {
        $output = $this->exec(new ExtractOptions(files: [$file = __DIR__ . '/../Extractor/Fixtures/complex_sprite.swf'], output: $this->outputDir, characters: [7, 13]));

        $this->assertStringContainsString('[1/1] Processing file: ' . $file . ' done', $output);
        $this->assertXmlFileEqualsXmlFile(__DIR__ . '/../Extractor/Fixtures/sprite-7.svg', $this->outputDir.'/complex_sprite/7.svg');
        $this->assertXmlFileEqualsXmlFile(__DIR__ . '/../Extractor/Fixtures/sprite-13.svg', $this->outputDir.'/complex_sprite/13.svg');
    }

    #[Test]
    public function charactersNotFound()
    {
        $output = $this->exec(new ExtractOptions(files: [$file = __DIR__ . '/../Extractor/Fixtures/complex_sprite.swf'], output: $this->outputDir, characters: [4000]));

        $this->assertStringContainsString('[1/1] Processing file: ' . $file . ' The character 4000 is missing in the SWF file', $output);
        $this->assertStringContainsString('Some errors occurred during the extraction process.', $output);
    }

    #[Test]
    public function exported()
    {
        $output = $this->exec(new ExtractOptions(files: [$file = __DIR__ . '/../Extractor/Fixtures/mob-leponge/mob-leponge.swf'], output: $this->outputDir, exported: ['walkR', 'walkL']));

        $this->assertStringContainsString('[1/1] Processing file: ' . $file . ' done', $output);
        $this->assertFileExists($this->outputDir . '/mob-leponge/walkR.svg');
        $this->assertFileExists($this->outputDir . '/mob-leponge/walkL.svg');
    }

    #[Test]
    public function exportedNotFound()
    {
        $output = $this->exec(new ExtractOptions(files: [$file = __DIR__ . '/../Extractor/Fixtures/complex_sprite.swf'], output: $this->outputDir, exported: ['not_found']));

        $this->assertStringContainsString('[1/1] Processing file: ' . $file . ' The character not_found is not exported in the SWF file', $output);
        $this->assertStringContainsString('Some errors occurred during the extraction process.', $output);
    }

    #[Test]
    public function allSprites()
    {
        $output = $this->exec(new ExtractOptions(files: [$file = __DIR__ . '/../Extractor/Fixtures/mob-leponge/mob-leponge.swf'], output: $this->outputDir, frames: [1], allSprites: true));

        $this->assertStringContainsString('[1/1] Processing file: ' . $file . ' done', $output);

        $files = scandir($this->outputDir.'/mob-leponge');
        $files = array_diff($files, ['.', '..']);
        natsort($files);
        $files = array_values($files);

        $this->assertEquals(['4.svg', '6.svg', '8.svg', '9.svg', '11.svg', '13.svg', '15.svg', '17.svg', '19.svg', '21.svg', '23.svg', '25.svg', '27.svg', '28.svg', '29_1.svg', '30.svg', '31.svg', '32.svg', '34.svg', '35.svg', '37.svg', '39.svg', '40_1.svg', '41.svg', '43.svg', '44_1.svg', '45.svg', '47.svg', '49.svg', '51.svg', '52_1.svg', '53.svg', '54.svg', '55.svg', '56_1.svg', '57.svg', '58_1.svg', '59.svg', '61.svg', '63.svg', '65.svg', '67.svg', '69.svg', '72.svg', '74_1.svg', '75.svg', '76.svg', '77_1.svg', '78.svg', '79.svg', '80.svg', '81.svg', '83.svg', '85_1.svg', '86_1.svg', '87.svg', '88_1.svg', '89.svg', '91.svg', '93_1.svg', '95.svg', '97.svg', '98_1.svg', '99_1.svg', '100.svg', '101_1.svg', '102.svg'], $files);
    }

    #[Test]
    public function allExported()
    {
        $output = $this->exec(new ExtractOptions(files: [$file = __DIR__ . '/../Extractor/Fixtures/mob-leponge/mob-leponge.swf'], output: $this->outputDir, frames: [1], allExported: true));

        $this->assertStringContainsString('[1/1] Processing file: ' . $file . ' done', $output);

        $files = scandir($this->outputDir.'/mob-leponge');
        $files = array_diff($files, ['.', '..']);
        natsort($files);
        $files = array_values($files);

        $this->assertEquals([
            'anim0L.svg',
            "anim0R.svg",
            "anim1L.svg",
            "anim1R.svg",
            "anim2L.svg",
            "anim2R.svg",
            "bonusL.svg",
            "bonusR.svg",
            "dieL.svg",
            "dieR.svg",
            "hitL.svg",
            "hitR.svg",
            "staticL.svg",
            "staticR.svg",
            "walkL.svg",
            "walkR.svg",
        ], $files);
    }

    #[Test]
    public function timeline()
    {
        $output = $this->exec(new ExtractOptions(files: [$file = __DIR__ . '/../Extractor/Fixtures/complex_sprite.swf'], output: $this->outputDir, timeline: true));

        $this->assertStringContainsString('[1/1] Processing file: ' . $file . ' done', $output);
        $this->assertXmlFileEqualsXmlFile(__DIR__.'/../Extractor/Fixtures/complex_sprite_frame.svg', $this->outputDir.'/complex_sprite/timeline.svg');
    }

    #[Test]
    public function variables()
    {
        $output = $this->exec(new ExtractOptions(files: [$file = __DIR__ . '/../Fixtures/lang_fr_801.swf'], output: $this->outputDir, variables: true));
        $this->assertStringContainsString('[1/1] Processing file: ' . $file . ' done', $output);

        $this->assertFileExists($this->outputDir.'/lang_fr_801/variables.json');
        $this->assertJsonFileEqualsJsonFile(__DIR__ . '/../Fixtures/lang_fr_801.json', $this->outputDir.'/lang_fr_801/variables.json');
    }

    #[Test]
    public function spriteAnimationSimple()
    {
        $this->exec(new ExtractOptions(files: [__DIR__ . '/../Extractor/Fixtures/1047/1047.swf'], output: $this->outputDir, characters: [65]));
        $this->assertCount(18, glob($this->outputDir.'/1047/*.svg'));

        for ($f = 1; $f <= 18; ++$f) {
            $this->assertXmlFileEqualsXmlFile(
                __DIR__ . '/../Extractor/Fixtures/1047/65_frames/65-'.($f-1).'.svg',
                $this->outputDir.'/1047/65_'.$f.'.svg'
            );
        }
    }

    #[Test]
    public function spriteWithCustomFormat()
    {
        $this->exec(new ExtractOptions(
            files: [__DIR__ . '/../Extractor/Fixtures/1047/1047.swf'],
            output: $this->outputDir,
            characters: [65],
            frames: [6],
            frameFormat: [
                new DrawableFormater(ImageFormat::Webp),
                new DrawableFormater(ImageFormat::Png),
            ]
        ));
        $this->assertCount(2, glob($this->outputDir.'/1047/*.*'));

        $this->assertImageStringEqualsImageFile(
            [
                __DIR__ . '/../Extractor/Fixtures/1047/65_frames/65-5.png',
                __DIR__ . '/../Extractor/Fixtures/1047/65_frames/65-5-rsvg.png',
                __DIR__ . '/../Extractor/Fixtures/1047/65_frames/65-5-inkscape12.png',
            ],
            file_get_contents($this->outputDir.'/1047/65_6.png'),
            0.05
        );

        $this->assertImageStringEqualsImageFile(
            [
                __DIR__ . '/../Extractor/Fixtures/1047/65_frames/65-5-lossless.webp',
                __DIR__ . '/../Extractor/Fixtures/1047/65_frames/65-5-rsvg.webp',
                __DIR__ . '/../Extractor/Fixtures/1047/65_frames/65-5-inkscape12.webp',
            ],
            file_get_contents($this->outputDir.'/1047/65_6.webp'),
            0.02
        );
    }

    #[Test]
    public function spriteAnimationAsAnimatedImage()
    {
        $this->exec(new ExtractOptions(
            files: [__DIR__ . '/../Extractor/Fixtures/mob-leponge/mob-leponge.swf'],
            output: $this->outputDir,
            exported: ['walkR'],
            fullAnimation: true,
            frameFormat: [],
            animationFormat: [
                new AnimationFormater(ImageFormat::Webp),
            ]
        ));
        $this->assertCount(1, glob($this->outputDir.'/mob-leponge/*.*'));

        $this->assertAnimatedImageStringEqualsImageFile(
            [
                __DIR__ . '/../Extractor/Fixtures/mob-leponge/walkR.webp',
                __DIR__ . '/../Extractor/Fixtures/mob-leponge/walkR-rsvg.webp',
                __DIR__ . '/../Extractor/Fixtures/mob-leponge/walkR-inkscape14.webp',
            ],
            file_get_contents($this->outputDir.'/mob-leponge/walkR.webp'),
            0.05
        );
    }

    #[Test]
    public function spriteAnimationEmbeddedWithFullAnimation()
    {
        $this->exec(new ExtractOptions(files: [__DIR__ . '/../Extractor/Fixtures/1047/1047.swf'], output: $this->outputDir, exported: ['anim0R']));
        $this->assertCount(1, glob($this->outputDir.'/1047/*.svg'));
        $this->assertXmlFileEqualsXmlFile(__DIR__ . '/../Extractor/Fixtures/1047/anim0R/frame_0.svg', $this->outputDir.'/1047/anim0R.svg');

        $this->exec(new ExtractOptions(files: [__DIR__ . '/../Extractor/Fixtures/1047/1047.swf'], output: $this->outputDir, exported: ['anim0R'], fullAnimation: true));
        $this->assertCount(41, glob($this->outputDir.'/1047/*.svg'));

        for ($f = 1; $f <= 40; ++$f) {
            $this->assertXmlFileEqualsXmlFile(
                __DIR__ . '/../Extractor/Fixtures/1047/anim0R/frame_'.($f-1).'.svg',
                $this->outputDir.'/1047/anim0R_'.$f.'.svg'
            );
        }
    }

    #[Test]
    public function exportShape()
    {
        $this->exec(new ExtractOptions(files: [__DIR__ . '/../Extractor/Fixtures/complex_sprite.swf'], output: $this->outputDir, characters: [8]));
        $this->assertXmlFileEqualsXmlFile(__DIR__ . '/../Extractor/Fixtures/shape_with_complex_fill.svg', $this->outputDir.'/complex_sprite/8.svg');
    }

    #[Test]
    public function exportImage()
    {
        $this->exec(new ExtractOptions(files: [__DIR__ . '/../Extractor/Fixtures/maps/0.swf'], output: $this->outputDir, characters: [507, 525]));
        $this->assertImageStringEqualsImageFile(
            __DIR__ . '/../Extractor/Fixtures/maps/jpeg-507.png',
            file_get_contents($this->outputDir.'/0/507.png')
        );
        $this->assertImageStringEqualsImageFile(
            __DIR__ . '/../Extractor/Fixtures/maps/jpeg-525.jpg',
            file_get_contents($this->outputDir.'/0/525.jpg')
        );
    }

    #[
        Test,
        TestWith(['{basename}.{ext}', '/1047.svg']),
        TestWith(['{dirname}-{name}.{ext}', '/1047-anim0R.svg']),
    ]
    public function customFilename(string $format, string $expected)
    {
        $this->exec(new ExtractOptions(
            files: [__DIR__ . '/../Extractor/Fixtures/1047/1047.swf'],
            output: $this->outputDir,
            outputFilename: $format,
            exported: ['anim0R'],
        ));

        $this->assertEquals([$this->outputDir.$expected], glob($this->outputDir.'/*.svg'));
    }

    #[Test]
    public function multipleFiles()
    {
        $output = $this->exec(new ExtractOptions(
            files: [
                __DIR__ . '/../Extractor/Fixtures/1047/1047.swf',
                __DIR__ . '/../Extractor/Fixtures/1597/1597.swf',
                __DIR__ . '/../Extractor/Fixtures/1700/1700.swf',
                __DIR__ . '/../Extractor/Fixtures/7022/7022.swf',
            ],
            output: $this->outputDir,
            outputFilename: '{basename}.{ext}',
            exported: ['staticR'],
        ));

        $this->assertStringContainsString(
            '[1/4] Processing file: ' . __DIR__ . '/../Extractor/Fixtures/1047/1047.swf' . ' done'.PHP_EOL .
            '[2/4] Processing file: ' . __DIR__ . '/../Extractor/Fixtures/1597/1597.swf' . ' done'.PHP_EOL .
            '[3/4] Processing file: ' . __DIR__ . '/../Extractor/Fixtures/1700/1700.swf' . ' done'.PHP_EOL .
            '[4/4] Processing file: ' . __DIR__ . '/../Extractor/Fixtures/7022/7022.swf' . ' done'.PHP_EOL,
            $output
        );

        $this->assertEquals([
            $this->outputDir.'/1047.svg',
            $this->outputDir.'/1597.svg',
            $this->outputDir.'/1700.svg',
            $this->outputDir.'/7022.svg',
        ], glob($this->outputDir.'/*.svg'));
    }

    private function exec(ExtractOptions $options): string
    {
        ob_start();

        $cmd = new ExtractCommand();
        $cmd->execute($options);

        return ob_get_clean();
    }
}
