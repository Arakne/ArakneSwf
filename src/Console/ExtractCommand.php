<?php

namespace Arakne\Swf\Console;

use Arakne\Swf\Extractor\Image\ImageBitsDefinition;
use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Image\JpegImageDefinition;
use Arakne\Swf\Extractor\Image\LosslessImageDefinition;
use Arakne\Swf\Extractor\MissingCharacter;
use Arakne\Swf\Extractor\Shape\ShapeDefinition;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\Extractor\Timeline\Timeline;
use Arakne\Swf\SwfFile;
use InvalidArgumentException;
use Throwable;

use function basename;
use function count;
use function dirname;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function json_encode;
use function mkdir;

final readonly class ExtractCommand
{
    public function execute(?ExtractOptions $options = null): int
    {
        $options ??= ExtractOptions::createFromCli();

        if ($options->help) {
            $this->usage($options);
            return 0;
        }

        if ($options->error) {
            $this->usage($options, $options->error);
            return 1;
        }

        $count = count($options->files);
        $success = true;

        foreach ($options->files as $i => $file) {
            echo '[', $i + 1, '/', $count, '] Processing file: ', $file, ' ';

            try {
                if ($this->process($options, $file)) {
                    echo 'done', PHP_EOL;
                } else {
                    $success = false;
                }
            } catch (Throwable $e) {
                echo 'error: ', $e, PHP_EOL;
                $success = false;
            }
        }

        if (!$success) {
            echo 'Some errors occurred during the extraction process.', PHP_EOL;
            return 1;
        }

        echo 'All files processed successfully.', PHP_EOL;
        return 0;
    }

    public function usage(ExtractOptions $options, ?string $error = null): void
    {
        if ($error !== null) {
            echo "Error: $error", PHP_EOL, PHP_EOL;
        }

        echo <<<EOT
Arakne-Swf by Vincent Quatrevieux
Extract resources from an SWF file.

Usage: 
    {$options->command} [options] <file> [<file> ...] <output>

Options:
    -h, --help            Show this help message
    -c, --character <id>  Specify the character id to extract. This option is repeatable.
    -e, --exported <name> Extract the character with the specified exported name. This option is repeatable.
    --frames <frames>     Frames to export, if applicable. Can be a single frame number, a range (e.g. 1-10), or "all".
                          By default, all frames will be exported. This option is repeatable.
    --full-animation      Extract the full animation for animated characters.
                          If set, the frames count will be computed on included sprites, instead of counting 
                          only the current character.
    --variables           Extract action script variables to JSON
    --all-sprites         Extract all sprites from the SWF file
    --all-exported        Extract all exported symbols from the SWF file
    --timeline            Extract the root SWF animation
    --output-filename     Define the filename pattern to use for the output files
                          (default: {$options->outputFilename})
                          Takes the following placeholders:
                          - {basename}: The base name of the SWF file
                          - {name}: The name or id of the character / exported symbol
                          - {ext}: The file extension (png, svg, json, etc.)
                          - {frame}/{_frame}: The frame number (1-based). {_frame} will prefix with "_" if needed

Arguments:
    <file>      The SWF file to extract resources from. Multiple files can be specified.
    <output>    The output directory where the extracted resources will be saved.

EOT;
    }

    public function process(ExtractOptions $options, string $file): bool
    {
        $swf = new SwfFile($file);

        if (!$swf->valid()) {
            echo "error: The file $file is not a valid SWF file", PHP_EOL;
            return false;
        }

        $extractor = new SwfExtractor($swf);
        $success = true;

        try {
            foreach ($options->characters as $characterId) {
                $success = $this->processCharacter($options, $file, (string)$characterId, $extractor->character($characterId)) && $success;
            }

            foreach ($options->exported as $name) {
                try {
                    $character = $extractor->byName($name);
                    $success = $this->processCharacter($options, $file, $name, $character) && $success;
                } catch (InvalidArgumentException) {
                    echo "The character $name is not exported in the SWF file", PHP_EOL;
                    $success = false;
                }
            }

            if ($options->allSprites) {
                foreach ($extractor->sprites() as $id => $sprite) {
                    $success = $this->processCharacter($options, $file, (string)$id, $sprite) && $success;
                }
            }

            if ($options->allExported) {
                foreach ($extractor->exported() as $name => $id) {
                    $character = $extractor->character($id);
                    $success = $this->processCharacter($options, $file, $name, $character) && $success;
                }
            }

            if ($options->timeline) {
                $success = $this->processCharacter($options, $file, 'timeline', $extractor->timeline(false)) && $success;
            }

            if ($options->variables) {
                $success = $this->processVariables($options, $file, 'variables', $swf) && $success;
            }
        } finally {
            $extractor->release();
        }

        return $success;
    }

    private function processCharacter(ExtractOptions $options, string $file, string $name, ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition|Timeline $character): bool
    {
        return match (true) {
            $character instanceof Timeline => $this->processTimeline($options, $file, $name, $character),
            $character instanceof SpriteDefinition => $this->processSprite($options, $file, $name, $character),
            $character instanceof ImageCharacterInterface => $this->processImage($options, $file, $name, $character),
            $character instanceof ShapeDefinition => $this->processShape($options, $file, $name, $character),
            $character instanceof MissingCharacter => (print "The character $name is missing in the SWF file" . PHP_EOL) && false,
        };
    }

    private function processVariables(ExtractOptions $options, string $file, string $name, SwfFile $swf): bool
    {
        $variables = $swf->variables();

        return $this->writeToOutputDir(
            json_encode($variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $file,
            $options,
            $name,
            'json'
        );
    }

    private function processTimeline(ExtractOptions $options, string $file, string $name, Timeline $timeline): bool
    {
        $framesCount = $timeline->framesCount($options->fullAnimation);

        if ($framesCount === 1) {
            return $this->writeToOutputDir($timeline->toSvg(), $file, $options, $name, 'svg');
        }

        if ($options->frames === null) {
            $success = true;

            for ($frame = 0; $frame < $framesCount; $frame++) {
                $svg = $timeline->toSvg($frame);
                $success = $this->writeToOutputDir($svg, $file, $options, $name, 'svg', $frame + 1) && $success;
            }

            return $success;
        }

        $success = true;

        foreach ($options->frames as $frame) {
            if ($frame > $framesCount) {
                break;
            }

            $svg = $timeline->toSvg($frame - 1);
            $success = $this->writeToOutputDir($svg, $file, $options, $name, 'svg', $frame) && $success;
        }

        return $success;
    }

    private function processImage(ExtractOptions $options, string $file, string $name, ImageCharacterInterface $image): bool
    {
        // @todo handle appropriate image format
        return $this->writeToOutputDir($image->toPng(), $file, $options, $name, 'png');
    }

    private function processSprite(ExtractOptions $options, string $file, string $name, SpriteDefinition $sprite): bool
    {
        return $this->processTimeline($options, $file, $name, $sprite->timeline());
    }

    private function processShape(ExtractOptions $options, string $file, string $name, ShapeDefinition $shape): bool
    {
        return $this->writeToOutputDir($shape->toSvg(), $file, $options, $name, 'svg');
    }

    private function writeToOutputDir(string $content, string $file, ExtractOptions $options, string $name, string $ext, ?int $frame = null): bool
    {
        $outputFile = $options->output . DIRECTORY_SEPARATOR . strtr($options->outputFilename, [
            '{basename}' => basename($file, '.swf'),
            '{name}' => $name,
            '{ext}' => $ext,
            '{frame}' => $frame !== null ? (string) $frame : '',
            '{_frame}' => $frame !== null ? '_' . (string) $frame : '',
        ]);

        if (file_exists($outputFile)) {
            echo "The file $outputFile already exists, skipping", PHP_EOL;
            return false;
        }

        $dir = dirname($outputFile);

        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            echo "Cannot create output directory: $dir", PHP_EOL;
            return false;
        }

        if (file_put_contents($outputFile, $content) === false) {
            echo "Cannot write to output file: $outputFile", PHP_EOL;
            return false;
        }

        return true;
    }
}
