<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * SWF.php: Macromedia Flash (SWF) file parser
 * Copyright (C) 2012 Thanos Efraimidis (4real.gr)
 *
 * Arakne-Swf: derived from SWF.php
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Parser;

use Arakne\Swf\Parser\Structure\SwfHeader;

use function var_dump;

/**
 * SWF file header parser
 */
readonly class SwfHdr
{
    public function __construct(
        private SwfReader $io,
        private SwfRec $rec,
    ) {}

    public function parseHeader(): SwfHeader
    {
        $signature = $this->io->readBytes(3);
        if ($signature === 'CWS') {
            $compressed = true;
        } elseif ($signature === 'FWS') {
            $compressed = false;
        } else {
            throw new \Exception(sprintf('Internal error: signature=[%s]', $signature));
        }

        $version = $this->io->readUI8();
        $fileLength = $this->io->readUI32();

        if ($compressed) {
            $this->io->doUncompress($fileLength);
        }

        $frameSize = $this->rec->collectRect();
        $frameRate = $this->io->readFixed8();
        $frameCount = $this->io->readUI16();

        return new SwfHeader(
            $signature,
            $version,
            $fileLength,
            $frameSize,
            $frameRate,
            $frameCount,
        );
    }
}
