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

namespace Arakne\Swf\Parser\Structure\Action;

use Arakne\Swf\Parser\SwfReader;
use Exception;

use function sprintf;

final readonly class ActionRecord
{
    public function __construct(
        /** @var non-negative-int */
        public int $offset,
        public Opcode $opcode,
        /** @var non-negative-int */
        public int $length,
        public mixed $data,
    ) {}

    /**
     * Read action record until the end of the current action block.
     *
     * @param SwfReader $reader
     * @param non-negative-int $end The end offset of the action block.
     *
     * @return list<ActionRecord>
     */
    public static function readCollection(SwfReader $reader, int $end): array
    {
        $actions =  [];

        while ($reader->offset < $end) {
            $offset = $reader->offset;
            $actionLength = 0;

            if (($actionCode = $reader->readUI8()) === 0) {
                $actions[] = new ActionRecord($offset, Opcode::Null, 0, null);
                continue;
            }

            if ($actionCode >= 0x80) {
                $actionLength = $reader->readUI16();
            }

            $opcode = Opcode::tryFrom($actionCode);

            if (!$opcode) {
                // @todo error on strict mode
                continue;
            }

            /** @var mixed $actionData */
            $actionData = $actionLength > 0 ? $opcode->readData($reader, $actionLength) : null;
            $actions[] = new ActionRecord($offset, $opcode, $actionLength, $actionData);
        }

        if ($reader->offset > $end) {
            // @todo error on strict mode
            throw new Exception(sprintf('Too many bytes read: offset=%d, end=%d', $reader->offset, $end));
        }

        return $actions;
    }
}
