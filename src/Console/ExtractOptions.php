<?php

namespace Arakne\Swf\Console;

use function Arakne\Swf\Bin\usage;
use function array_map;
use function array_pop;
use function array_push;
use function array_slice;
use function count;
use function explode;
use function getopt;
use function intval;
use function is_dir;
use function mkdir;
use function range;
use function realpath;
use function strtolower;
use function strval;

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
    ) {}

    /**
     * Create the options from the command line arguments.
     */
    public static function createFromCli(): self
    {
        global $argv;

        $cmd = $argv[0];
        $options = getopt(
            'hc:e:',
            [
                'help', 'character:', 'all-sprites', 'all-exported', 'variables',
                'timeline', 'exported:', 'output-filename:', 'frames:', 'full-animation',
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

        if (!is_dir($output) && !mkdir($output, 0775, true)) {
            return new self($cmd, error: "Cannot create output directory: $output");
        }

        return new self(
            $cmd,
            help: isset($options['h']) || isset($options['help']),
            files: $arguments,
            output: realpath($output),
            outputFilename: $options['output-filename'] ?? self::DEFAULT_OUTPUT_FILENAME,
            characters: array_map(intval(...), [...(array)($options['c'] ?? []), ...(array)($options['character'] ?? [])]),
            exported: array_map(strval(...), [...(array)($options['e'] ?? []), ...(array)($options['exported'] ?? [])]),
            frames: self::parseFramesOption($options),
            fullAnimation: isset($options['full-animation']),
            allSprites: isset($options['all-sprites']),
            allExported: isset($options['all-exported']),
            timeline: isset($options['timeline']),
            variables: isset($options['variables']),
        );
    }

    /**
     * @param array $options
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
            if (strtolower($range) === 'all') {
                return null;
            }

            $range = explode('-', $range, 2);

            if (count($range) === 1) {
                $frames[] = max((int) $range[0], 1);
            } else {
                array_push($frames, ...range((int) $range[0], (int) $range[1]));
            }
        }

        return $frames;
    }
}
