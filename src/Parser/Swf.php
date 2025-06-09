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

use Arakne\Swf\Parser\Error\ErrorCollector;
use Arakne\Swf\Parser\Structure\SwfHeader;
use Arakne\Swf\Parser\Structure\SwfTagPosition;

/**
 * Facade for parse and extract data from a SWF file
 */
readonly class Swf
{
    public SwfHeader $header;

    /**
     * @var list<SwfTagPosition>
     */
    public array $tags; // Tags

    private SwfReader $io;
    private SwfRec $rec;
    private SwfHdr $hdr;
    private SwfTag $tag;

    /**
     * @param string $binary The content of the SWF file
     * @param ErrorCollector|null $errorCollector The error collector to use
     */
    public function __construct(string $binary, ?ErrorCollector $errorCollector = null)
    {
        $this->io = new SwfReader($binary);
        $this->rec = new SwfRec($this->io);
        $this->hdr = new SwfHdr($this->io);
        $this->header = $this->hdr->parseHeader();
        $this->tag = new SwfTag($this->io, $this->rec, $this->header->version, $errorCollector);
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
    final public function parseTag(SwfTagPosition $tag): object
    {
        return $this->tag->parseTag($tag);
    }
}
