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
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Avm\Api;

use Override;

use Traversable;

use function array_fill;
use function count;
use function is_float;

/**
 * Emulates an ActionScript Array object.
 *
 * @property int $length The length of the array.
 */
class ScriptArray extends ScriptObject
{
    /**
     * @var array<int, mixed>
     */
    private array $values;

    /**
     * @param mixed ...$values
     * @no-named-arguments
     */
    public function __construct(mixed ...$values)
    {
        parent::__construct();

        if (count($values) === 1) {
            $values = array_fill(0, (int) $values[0], null);
        }

        $this->values = $values;
        $this->addProperty('length', fn () => count($this->values), $this->setLength(...));
    }

    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[$offset] ?? parent::offsetGet($offset);
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Integer or float that is an integer
        if (is_int($offset) || (is_float($offset) && $offset == (int) $offset)) {
            $this->values[(int) $offset] = $value;
            return;
        }

        parent::offsetSet($offset, $value);
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]) || parent::offsetExists($offset);
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        // Integer or float that is an integer
        if (is_int($offset) || (is_float($offset) && $offset == (int) $offset)) {
            $this->values[$offset] = null;
        } else {
            parent::offsetUnset($offset);
        }
    }

    #[Override]
    public function jsonSerialize(): array
    {
        // Ignore computed properties
        return $this->values + $this->properties;
    }

    #[Override]
    public function getIterator(): Traversable
    {
        yield from $this->values;
    }

    private function setLength(int $length): void
    {
        if ($length === 0) {
            $this->values = [];
            return;
        }

        $currentLength = count($this->values);

        if ($length < $currentLength) {
            $this->values = array_slice($this->values, 0, $length);
            return;
        }

        for ($i = $currentLength; $i < $length; $i++) {
            $this->values[] = null;
        }
    }
}
