<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2025 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

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

use function assert;
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
                                      - {dirname}: The name of the directory containing the SWF file
                --frame-format <format>
                                      Specify the format to use for the sprite or timeline frames. This option is repeatable.
                                      The format is <options>:<filetype>@<width>x<height>, where:
                                      - <options> are optional options to apply to the output, separated by ":".
                                      - <filetype> is the type of file to generate (svg, png, gif, webp).
                                          When an animated file is requested, all frames will be exported, even if the --frames option is used.
                                      - <width> is the width of the output image (optional).
                                      - <height> is the height of the output image (optional). 
                                          If only the width is specified, the height will be set to the same value.
                                      Availables options:
                                      - a/anim/animated: (gif, webp) Export the frames as an animated file.
                                                         When is requested, all frames will be exported, even if the --frames option is used.
                                      - lossless: (webp) Use lossless compression for the output image.
                                      - quality=<number>: (webp, jpeg) Set the quality (i.e. lossy compression) of the output image (0-100).
                                      - compression=<number>: (png, webp) Set the compression level of the output image (0-6 for webp, 0-9 for png).
                                      - format=<format>: (png) Set the PNG format to use (e.g. png8, png24, png32).
                                      - bit-depth=<number>: (png) Set the bit depth of the output image.
                                      - sampling=<string>: (jpeg) Set the sampling factor for the JPEG image (e.g. 420, 422, 444).
                                      - size=<string>: (jpeg) Set the maximum file size for the JPEG image (e.g. 100k, 1M).
                                      - loop=<number>: (gif) Set the number of loops for the GIF animation (0 for infinite loop).


            Arguments:
                <file>      The SWF file to extract resources from. Multiple files can be specified.
                <output>    The output directory where the extracted resources will be saved.
            
            Examples:
                Extract all exported symbols from a SWF file
                    {$options->command} --all-exported --output-filename myfile.swf export
                    
                Extract the first frame of the main timeline of a SWF file as PNG of size 128x128
                    {$options->command} --timeline --frames 1 --frame-format png@128x128 --output-filename {basename}.{ext} myfile.swf export

                Extract a single sprite animation, with all its sub-animations
                    {$options->command} -e myAnim --full-animation --output-filename {basename}/{frame}.{ext} myfile.swf export

                Extract an animation as lossless animated webp
                    {$options->command} -e myAnim --full-animation --frame-format a:lossless:webp --output-filename {basename}/{frame}.{ext} myfile.swf export

                Extract a character as jpeg with quality 80 and 4:2:0 sampling factors
                    {$options->command} -c 123 --frame-format quality=80:sampling=420:jpeg myfile.swf export

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
                $success = $this->processCharacter($options, $swf, (string)$characterId, $extractor->character($characterId)) && $success;
            }

            foreach ($options->exported as $name) {
                try {
                    $character = $extractor->byName($name);
                    $success = $this->processCharacter($options, $swf, $name, $character) && $success;
                } catch (InvalidArgumentException) {
                    echo "The character $name is not exported in the SWF file", PHP_EOL;
                    $success = false;
                }
            }

            if ($options->allSprites) {
                foreach ($extractor->sprites() as $id => $sprite) {
                    $success = $this->processCharacter($options, $swf, (string)$id, $sprite) && $success;
                }
            }

            if ($options->allExported) {
                foreach ($extractor->exported() as $name => $id) {
                    $character = $extractor->character($id);
                    $success = $this->processCharacter($options, $swf, (string) $name, $character) && $success;
                }
            }

            if ($options->timeline) {
                $success = $this->processCharacter($options, $swf, 'timeline', $extractor->timeline(false)) && $success;
            }

            if ($options->variables) {
                $success = $this->processVariables($options, 'variables', $swf) && $success;
            }
        } finally {
            $extractor->release();
        }

        return $success;
    }

    private function processCharacter(ExtractOptions $options, SwfFile $file, string $name, ShapeDefinition|SpriteDefinition|MissingCharacter|ImageBitsDefinition|JpegImageDefinition|LosslessImageDefinition|Timeline $character): bool
    {
        return match (true) {
            $character instanceof Timeline => $this->processTimeline($options, $file, $name, $character),
            $character instanceof SpriteDefinition => $this->processSprite($options, $file, $name, $character),
            $character instanceof ImageCharacterInterface => $this->processImage($options, $file->path, $name, $character),
            $character instanceof ShapeDefinition => $this->processShape($options, $file->path, $name, $character),
            $character instanceof MissingCharacter => (print "The character $name is missing in the SWF file" . PHP_EOL) && false,
        };
    }

    private function processVariables(ExtractOptions $options, string $name, SwfFile $swf): bool
    {
        $variables = $swf->variables();
        $content = json_encode($variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        assert($content !== false);

        return $this->writeToOutputDir(
            $content,
            $swf->path,
            $options,
            $name,
            'json'
        );
    }

    private function processTimeline(ExtractOptions $options, SwfFile $file, string $name, Timeline $timeline): bool
    {
        $success = true;

        foreach ($options->animationFormat as $formater) {
            $success = $this->writeToOutputDir(
                $formater->format($timeline, $file->frameRate(), $options->fullAnimation),
                $file->path,
                $options,
                $name,
                $formater->extension()
            ) && $success;
        }

        $framesCount = $timeline->framesCount($options->fullAnimation);

        if ($framesCount === 1) {
            return $this->processTimelineFrame($options, $file->path, $name, $timeline) && $success;
        }

        if ($options->frames === null) {
            for ($frame = 0; $frame < $framesCount; $frame++) {
                $success = $this->processTimelineFrame($options, $file->path, $name, $timeline, $frame) && $success;
            }

            return $success;
        }

        foreach ($options->frames as $frame) {
            if ($frame > $framesCount) {
                break;
            }

            $success = $this->processTimelineFrame($options, $file->path, $name, $timeline, $frame - 1) && $success;
        }

        return $success;
    }

    /**
     * @param ExtractOptions $options
     * @param string $file
     * @param string $name
     * @param Timeline $timeline
     * @param non-negative-int|null $frame
     *
     * @return bool
     */
    private function processTimelineFrame(ExtractOptions $options, string $file, string $name, Timeline $timeline, ?int $frame = null): bool
    {
        $success = true;

        foreach ($options->frameFormat as $formater) {
            $success = $this->writeToOutputDir($formater->format($timeline, $frame ?? 0), $file, $options, $name, $formater->extension(), $frame !== null ? $frame + 1 : null) && $success;
        }

        return $success;
    }

    private function processImage(ExtractOptions $options, string $file, string $name, ImageCharacterInterface $image): bool
    {
        $data = $image->toBestFormat();

        return $this->writeToOutputDir($data->data, $file, $options, $name, $data->type->extension());
    }

    private function processSprite(ExtractOptions $options, SwfFile $file, string $name, SpriteDefinition $sprite): bool
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
            '{dirname}' => basename(dirname($file)),
        ]);

        if (file_exists($outputFile)) {
            echo "The file $outputFile already exists, skipping", PHP_EOL;
            return false;
        }

        $dir = dirname($outputFile);

        if (!is_dir($dir) && !mkdir($dir, 0o775, true)) {
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
