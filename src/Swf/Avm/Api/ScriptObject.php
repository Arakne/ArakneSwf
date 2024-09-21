<?php

namespace Arakne\Swf\Avm\Api;

use ArrayAccess;
use Closure;
use IteratorAggregate;
use JsonSerializable;
use Override;

use Traversable;

use function is_int;
use function is_string;

/**
 * Base object class for ActionScript objects.
 */
class ScriptObject implements ArrayAccess, JsonSerializable, IteratorAggregate
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
    ) {
    }

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

    public function __set(string $name, $value): void
    {
        $this->setPropertyValue($name, $value);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return ($this->properties[$name])(...$arguments);
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
