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

namespace Arakne\Swf\Extractor\Drawer\Converter\Renderer;

use Imagick;
use Override;

use RuntimeException;

use function escapeshellarg;
use function fclose;
use function fwrite;
use function proc_close;
use function proc_open;
use function shell_exec;
use function sprintf;
use function stream_get_contents;

/**
 * Base implementation for command-based SVG renderers, using UNIX pipe to communicate with the command.
 */
abstract readonly class AbstractCommandImagickSvgRenderer implements ImagickSvgRendererInterface
{
    public function __construct(
        protected string $command = '',
    ) {}

    #[Override]
    final public function open(string $svg, string $backgroundColor): Imagick
    {
        $proc = proc_open(
            $this->buildCommand($this->command, $backgroundColor),
            [
                ['pipe', 'r'],
                ['pipe', 'w'],
                ['pipe', 'w'],
            ],
            $pipes
        );

        if ($proc === false) {
            throw new RuntimeException('Failed to open process for rsvg-convert');
        }

        try {
            fwrite($pipes[0], $svg);
            fclose($pipes[0]);

            $png = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            if (!$png) {
                throw new RuntimeException('Svg conversion failed: ' . stream_get_contents($pipes[2]));
            }
        } finally {
            proc_close($proc);
        }

        $img = new Imagick();
        $img->setBackgroundColor($backgroundColor);
        $img->readImageBlob($png);

        return $img;
    }

    #[Override]
    public function supported(): bool
    {
        return !empty(shell_exec(sprintf('which %s 2> /dev/null', escapeshellarg($this->command))));
    }

    /**
     * Build the CLI command to execute.
     *
     * @param string $command The command executable.
     * @param string $backgroundColor The background color. Note: this parameter should be escaped.
     *
     * @return string The command to execute.
     */
    abstract protected function buildCommand(string $command, string $backgroundColor): string;
}
