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
use Arakne\Swf\Parser\Structure\SwfTagPosition;

readonly class Swf
{
    public SwfHeader $header;

    /**
     * @var list<SwfTagPosition>
     */
    public array $tags; // Tags

    private SwfIO $io;
    private SwfRec $rec;
    private SwfHdr $hdr;
    private SwfTag $tag; // SWFtag for tags

    /**
     * @param string $binary The content of the SWF file
     */
    public function __construct(string $binary)
    {
        $this->io = new SwfIO($binary);
        $this->rec = new SwfRec($this->io);
        $this->hdr = new SwfHdr($this->io, $this->rec);
        $this->header = $this->hdr->parseHeader();
        $this->tag = new SwfTag($this->io, $this->rec, $this->header->version);
        $this->tags = $this->tag->parseTags();
    }

    /**
     * Parse the given tag data
     *
     * @param SwfTagPosition $tag
     * @return object
     *
     * @see SwfTag::parseTag()
     */
    public function parseTag(SwfTagPosition $tag): object
    {
        return $this->tag->parseTag($tag);
    }
}
