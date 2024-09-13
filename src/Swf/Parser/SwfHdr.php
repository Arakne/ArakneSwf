<?php

declare(strict_types=1);

/*
    SWF.php: Macromedia Flash (SWF) file parser
    Copyright (C) 2012 Thanos Efraimidis (4real.gr)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace Arakne\Swf\Parser;

use Arakne\Swf\Parser\Structure\SwfHeader;

readonly class SwfHdr
{
    public function __construct(
        private SwfIO $io,
        private SwfRec $rec,
    ) {}

    public function parseHeader(): SwfHeader
    {
        $signature = $this->io->collectBytes(3);
        if ($signature === 'CWS') {
            $compressed = true;
        } else if ($signature === 'FWS') {
            $compressed = false;
        } else {
            throw new \Exception(sprintf('Internal error: signature=[%s]', $signature));
        }

        $version = $this->io->collectUI8();
        $fileLength = $this->io->collectUI32();

        if ($compressed) {
            $this->io->doUncompress();
        }

        $frameSize = $this->rec->collectRect();
        $frameRate = $this->io->collectFixed8();
        $frameCount = $this->io->collectUI16();

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
