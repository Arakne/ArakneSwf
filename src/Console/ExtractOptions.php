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

use Arakne\Swf\Extractor\Drawer\Converter\AnimationFormater;
use Arakne\Swf\Extractor\Drawer\Converter\DrawableFormater;
use Arakne\Swf\Extractor\Drawer\Converter\FitSizeResizer;
use Arakne\Swf\Extractor\Drawer\Converter\ImageFormat;
use InvalidArgumentException;

use function array_fill_keys;
use function array_map;
use function array_pop;
use function array_push;
use function array_slice;
use function count;
use function explode;
use function getopt;
use function is_dir;
use function is_string;
use function mkdir;
use function range;
use function realpath;
use function sprintf;
use function strtolower;

/**
 * CLI options for the swf-extract command
 */
final readonly class ExtractOptions
{
    public const string DEFAULT_OUTPUT_FILENAME = '{basename}/{name}{_frame}.{ext}';

    public function __construct(
        /**
         * The executable name
         * Should be argv[0]
         */
        public string $command = 'swf-extract',

        /**
         * Error for invalid command line arguments
         * If this value is not null, the error message should be displayed, with the usage
         */
        public ?string $error = null,

        /**
         * Show the help message
         */
        public bool $help = false,

        /**
         * SWF files to extract
         *
         * @var string[]
         */
        public array $files = [],

        /**
         * The output directory
         * If not set, the current directory will be used
         */
        public string $output = '',

        /**
         * The filename pattern to use for the output files
         */
        public string $outputFilename = self::DEFAULT_OUTPUT_FILENAME,

        /**
         * List of character ids to extract
         *
         * @var int[]
         */
        public array $characters = [],

        /**
         * List of exported names to extract
         *
         * @var string[]
         */
        public array $exported = [],

        /**
         * List of frames to extract.
         * If null, all frames will be extracted.
         *
         * Frames numbers are 1-based.
         *
         * @var positive-int[]|null
         */
        public ?array $frames = null,

        /**
         * Extract the full animation for animated characters.
         * If true, frames from embedded sprites will be extracted as well,
         * instead of just the frames count from the current character.
         */
        public bool $fullAnimation = false,

        /**
         * Extract all sprites
         */
        public bool $allSprites = false,

        /**
         * Extract all exported symbols
         */
        public bool $allExported = false,

        /**
         * Extract the root SWF animation
         */
        public bool $timeline = false,

        /**
         * Extract action script variables to JSON
         */
        public bool $variables = false,

        /**
         * The format to use for render the frames/sprites.
         * If not set, the default format will be used (SVG)
         *
         * @var list<DrawableFormater>
         */
        public array $frameFormat = [new DrawableFormater(ImageFormat::Svg)],

        /**
         * The format to use for render the frames/sprites as an animated image.
         *
         * @var list<AnimationFormater>
         */
        public array $animationFormat = [],
    ) {}

    /**
     * Create the options from the command line arguments.
     */
    public static function createFromCli(): self
    {
        global $argv;

        $cmd = $argv[0];
        /**
         * @var array<string, string|bool|list<string|bool>> $options
         * @phpstan-ignore varTag.nativeType
         */
        $options = getopt(
            'hc:e:',
            [
                'help', 'character:', 'all-sprites', 'all-exported', 'variables',
                'timeline', 'exported:', 'output-filename:', 'frames:', 'full-animation',
                'frame-format:',
            ],
            $argsOffset
        );
        $arguments = array_slice($argv, $argsOffset);

        // By default, show the help message
        if (!$options && !$arguments) {
            return new self($cmd, help: true);
        }

        if (count($arguments) < 2) {
            return new self($cmd, error: 'Not enough arguments: <file> and <output> are required');
        }

        $output = array_pop($arguments);

        if (!is_dir($output) && !mkdir($output, 0o775, true)) {
            return new self($cmd, error: "Cannot create output directory: $output");
        }

        $output = realpath($output);

        if (!$output) {
            return new self($cmd, error: "Cannot resolve output directory: $output");
        }

        $outputFilename = self::DEFAULT_OUTPUT_FILENAME;

        if (isset($options['output-filename'])) {
            $outputFilename = $options['output-filename'];

            if (!is_string($outputFilename)) {
                return new self($cmd, error: 'The --output-filename option must take only one value');
            }
        }

        try {
            [$frameFormat, $animationFormat] = self::parseFormatOption($options, 'frame-format');

            return new self(
                $cmd,
                help: isset($options['h']) || isset($options['help']),
                files: $arguments,
                output: $output,
                outputFilename: $outputFilename,
                characters: array_map(intval(...), [...(array)($options['c'] ?? []), ...(array)($options['character'] ?? [])]),
                exported: array_map(strval(...), [...(array)($options['e'] ?? []), ...(array)($options['exported'] ?? [])]),
                frames: self::parseFramesOption($options),
                fullAnimation: isset($options['full-animation']),
                allSprites: isset($options['all-sprites']),
                allExported: isset($options['all-exported']),
                timeline: isset($options['timeline']),
                variables: isset($options['variables']),
                frameFormat: $frameFormat,
                animationFormat: $animationFormat,
            );
        } catch (InvalidArgumentException $e) {
            return new self($cmd, error: $e->getMessage());
        }
    }

    /**
     * @param array<string, string|bool|list<string|bool>> $options
     * @return positive-int[]|null
     */
    private static function parseFramesOption(array $options): ?array
    {
        $option = $options['frames'] ?? null;

        if ($option === null) {
            return null;
        }

        $frames = [];

        foreach ((array) $option as $range) {
            $range = (string) $range;

            if (strtolower($range) === 'all') {
                return null;
            }

            $range = explode('-', $range, 2);

            if (count($range) === 1) {
                $frames[] = max((int) $range[0], 1);
            } else {
                [$min, $max] = $range;

                $min = max((int) $min, 1);
                $max = max((int) $max, 1);

                array_push($frames, ...range($min, $max));
            }
        }

        return $frames;
    }

    /**
     * @param array<string, string|bool|list<string|bool>> $options
     * @param string $optionName
     *
     * @return list{list<DrawableFormater>, list<AnimationFormater>}
     */
    private static function parseFormatOption(array $options, string $optionName): array
    {
        $frameFormatters = [];
        $animationFormatters = [];

        foreach ((array) ($options[$optionName] ?? []) as $format) {
            $formatStr = explode('@', strtolower((string) $format), 2);
            $formatAndFlags = explode(':', $formatStr[0]);
            $formatName = array_pop($formatAndFlags);
            $options = array_fill_keys($formatAndFlags, true);
            $isAnimation = isset($options['a']) || isset($options['anim']) || isset($options['animated']) || isset($options['animation']);

            $format = ImageFormat::tryFrom($formatName) ?? throw new InvalidArgumentException(sprintf('Invalid value for option --%s: the format %s is not supported', $optionName, $formatName));

            if (isset($formatStr[1])) {
                $size = explode('x', $formatStr[1], 2);

                $width = max((int) $size[0], 1);
                $height = max((int) ($size[1] ?? $size[0]), 1);

                $resizer = new FitSizeResizer($width, $height);
            } else {
                $resizer = null;
            }

            if ($isAnimation) {
                $animationFormatters[] = new AnimationFormater($format, $resizer, $options);
            } else {
                $frameFormatters[] = new DrawableFormater($format, $resizer, $options);
            }
        }

        if (!$frameFormatters && !$animationFormatters) {
            $frameFormatters[] = new DrawableFormater(ImageFormat::Svg);
        }

        return [$frameFormatters, $animationFormatters];
    }
}
