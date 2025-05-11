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

use ArrayAccess;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Override;
use Traversable;

use function count;
use function is_callable;
use function is_int;
use function is_string;

/**
 * Base object class for ActionScript objects.
 *
 * @implements ArrayAccess<array-key, mixed>
 * @implements IteratorAggregate<array-key, mixed>
 */
class ScriptObject implements ArrayAccess, JsonSerializable, IteratorAggregate, Countable
{
    public function __construct(
        /**
         * @var array<array-key, mixed>
         */
        protected array $properties = [],

        /**
         * @var array<array-key, Closure():mixed>
         */
        protected array $getters = [],

        /**
         * @var array<array-key, Closure(mixed):void>
         */
        protected array $setters = [],
    ) {}

    /**
     * Define a new computed property.
     *
     * The method will return false if the property is invalid.
     *
     * @param string $name The new property name. If already exists, it will be overwritten.
     * @param Closure():mixed $getter The getter closure.
     * @param (Closure(mixed):void)|null $setter The setter closure. Takes the new value as argument. If not provided, the property will be read-only.
     *
     * @return bool true if the property was added, false on failure.
     */
    public function addProperty(string $name, Closure $getter, ?Closure $setter = null): bool
    {
        if ($name === '') {
            return false;
        }

        $this->getters[$name] = $getter;

        if ($setter !== null) {
            $this->setters[$name] = $setter;
        }

        return true;
    }

    #[Override]
    public function count(): int
    {
        return count($this->properties) + count($this->getters);
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset) && !is_int($offset)) {
            return false;
        }

        return isset($this->properties[$offset]) || isset($this->getters[$offset]);
    }

    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getPropertyValue($offset);
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setPropertyValue($offset, $value);
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->properties[$offset]);
    }

    public function __get(string $name): mixed
    {
        return $this->getPropertyValue($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setPropertyValue($name, $value);
    }

    /**
     * @param string $name
     * @param mixed[] $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        $method = $this->properties[$name] ?? null;

        if (!is_callable($method)) {
            return null;
        }

        return $method(...$arguments);
    }

    public function __isset(string $name): bool
    {
        return isset($this->properties[$name]) || isset($this->getters[$name]);
    }

    #[Override]
    public function jsonSerialize(): mixed
    {
        $properties = $this->properties;

        foreach ($this->getters as $name => $getter) {
            $properties[$name] = $getter();
        }

        return $properties;
    }

    #[Override]
    public function getIterator(): Traversable
    {
        yield from $this->properties;

        foreach ($this->getters as $name => $getter) {
            yield $name => $getter();
        }
    }

    private function getPropertyValue(mixed $property): mixed
    {
        if (!is_string($property) && !is_int($property)) {
            return null;
        }

        $getter = $this->getters[$property] ?? null;

        if ($getter !== null) {
            return $getter();
        }

        return $this->properties[$property] ?? null;
    }

    private function setPropertyValue(mixed $property, mixed $value): void
    {
        if (!is_string($property) && !is_int($property)) {
            return;
        }

        $setter = $this->setters[$property] ?? null;

        if ($setter !== null) {
            $setter($value);
            return;
        }

        $this->properties[$property] = $value;
    }
}
