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

use Arakne\Swf\Parser\Error\Errors;
use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Error\ParserOutOfBoundException;
use Arakne\Swf\Parser\SwfReader;
use Exception;

use function sprintf;

/**
 * Store primitive ActionScript value with its type
 */
final readonly class Value
{
    public function __construct(
        public Type $type,
        /**
         * The parsed value
         */
        public int|float|string|bool|null $value,
    ) {}

    /**
     * Read a collection of values from the reader.
     * Values will be read until the specified length is reached.
     *
     * @param SwfReader $reader
     * @param non-negative-int $length Length of the collection to read, in bytes.
     *
     * @return list<self>
     */
    public static function readCollection(SwfReader $reader, int $length): array
    {
        $values = [];
        $bytePosEnd = $reader->offset + $length;

        if ($bytePosEnd > $reader->end) {
            if ($reader->errors & Errors::OUT_OF_BOUNDS) {
                throw ParserOutOfBoundException::createReadTooManyBytes($reader->offset, $reader->end, $length);
            }

            $bytePosEnd = $reader->end;
        }

        while ($reader->offset < $bytePosEnd) {
            $typeId = $reader->readUI8();
            $type = Type::tryFrom($typeId);

            if ($type === null) {
                if ($reader->errors & Errors::INVALID_DATA) {
                    throw new ParserInvalidDataException(
                        sprintf('Invalid value type "%d" at offset %d', $typeId, $reader->offset),
                        $reader->offset
                    );
                }

                continue;
            }

            $values[] = new Value(
                $type,
                match ($type) {
                    Type::String => $reader->readNullTerminatedString(),
                    Type::Float => $reader->readFloat(),
                    Type::Null => null,
                    Type::Undefined => null,
                    Type::Register => $reader->readUI8(),
                    Type::Boolean => $reader->readUI8() === 1,
                    Type::Double => $reader->readDouble(),
                    Type::Integer => $reader->readSI32(),
                    Type::Constant8 => $reader->readUI8(),
                    Type::Constant16 => $reader->readUI16(),
                }
            );
        }

        return $values;
    }
}
