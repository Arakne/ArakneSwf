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

namespace Arakne\Swf\Avm;

use Arakne\Swf\Avm\Api\ScriptArray;
use Arakne\Swf\Avm\Api\ScriptObject;
use Arakne\Swf\Parser\Structure\Action\ActionRecord;
use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;
use Exception;

use function array_pop;
use function array_reverse;
use function array_splice;
use function assert;
use function count;
use function is_array;
use function is_callable;
use function is_float;
use function is_int;
use function is_integer;
use function is_object;
use function is_string;
use function method_exists;
use function var_dump;

/**
 * Execute the parsed AVM bytecode.
 * This class is stateless, so the state must be passed as argument.
 */
final readonly class Processor
{
    public function __construct(
        /**
         * Allow to call methods or functions.
         * If false, the processor will always return null for method calls, without executing them.
         */
        private bool $allowFunctionCall = true,
    ) {}

    /**
     * Run the given actions and return the final state.
     *
     * @param list<ActionRecord> $actions
     * @param State|null $state
     *
     * @return State
     */
    public function run(array $actions, ?State $state = null): State
    {
        $state ??= new State();

        foreach ($actions as $action) {
            $this->execute($state, $action);
        }

        return $state;
    }

    /**
     * Execute a single instruction.
     *
     * @param State $state The current state.
     * @param ActionRecord $action The action to execute.
     *
     * @return void
     * @throws Exception
     */
    public function execute(State $state, ActionRecord $action): void
    {
        match ($action->opcode) {
            Opcode::ActionConstantPool =>
                /* @phpstan-ignore assign.propertyType */
                $state->constants = $action->data,
            Opcode::ActionPush =>
                /** @phpstan-ignore argument.unpackNonIterable, argument.type */
                array_push($state->stack, ...self::toPhpValues($state, ...$action->data)),
            Opcode::ActionSetVariable => $this->setVariable($state),
            Opcode::ActionGetVariable => $this->getVariable($state),
            Opcode::ActionGetMember => $this->getMember($state),
            Opcode::ActionCallMethod => $this->callMethod($state),
            Opcode::ActionPop => array_pop($state->stack),
            Opcode::ActionNewObject => $this->newObject($state),
            Opcode::ActionInitObject => $this->initObject($state),
            Opcode::ActionInitArray => $this->initArray($state),
            Opcode::ActionSetMember => $this->setMember($state),
            Opcode::ActionToString => $this->toString($state),
            Opcode::ActionToNumber => $this->toNumber($state),
            Opcode::ActionCallFunction => $this->callFunction($state),
            Opcode::Null => null,
            //default => null,
            default => throw new \Exception('Unknown action: '.$action->opcode->name.' '.json_encode($action).' Stack: '.json_encode($state->stack)),
        };
    }

    /**
     * Parse ActionScript value to PHP value.
     *
     * @param State $state
     * @param Value ...$values
     *
     * @return list<mixed>
     */
    public static function toPhpValues(State $state, Value ...$values): array
    {
        $parsed = [];

        foreach ($values as $value) {
            $parsed[] = match ($value->type) {
                Type::Constant8, Type::Constant16 => $state->constants[(int) $value->value],
                // @todo register
                default => $value->value,
            };
        }

        return $parsed;
    }

    private function setVariable(State $state): void
    {
        $value = array_pop($state->stack);
        $name = array_pop($state->stack);

        $state->variables[$name] = $value;
    }

    private function getVariable(State $state): void
    {
        $index = count($state->stack) - 1;
        assert($index > 0);

        // @phpstan-ignore assign.propertyType
        $state->stack[$index] = $state->variables[$state->stack[$index]] ?? null;
    }

    private function getMember(State $state): void
    {
        $propertyName = array_pop($state->stack);
        $scriptObject = array_pop($state->stack);

        if ($scriptObject === null) {
            $state->stack[] = null;
            return;
        }

        if (is_array($scriptObject) || ($scriptObject instanceof ScriptArray && is_int($propertyName))) {
            $state->stack[] = $scriptObject[$propertyName] ?? null;
            return;
        }

        $state->stack[] = $scriptObject->$propertyName ?? null;
    }

    private function callMethod(State $state): void
    {
        $methodName = (string) array_pop($state->stack);
        $scriptObject = array_pop($state->stack);
        $argumentCount = (int) array_pop($state->stack);
        $args = $argumentCount > 0 ? array_splice($state->stack, -$argumentCount) : [];

        if (!$this->allowFunctionCall) {
            $state->stack[] = null;
            return;
        }

        if (!is_object($scriptObject)) {
            $state->stack[] = null;
            return;
        }

        if (!method_exists($scriptObject, $methodName) && (!$scriptObject instanceof ScriptObject || !isset($scriptObject->$methodName) || !is_callable($scriptObject->$methodName))) {
            $state->stack[] = null;
            return;
        }

        $state->stack[] = $scriptObject->$methodName(...array_reverse($args));
    }

    private function newObject(State $state): void
    {
        $type = (string) array_pop($state->stack);
        $argumentCount = (int) array_pop($state->stack);
        $args = $argumentCount > 0 ? array_reverse(array_splice($state->stack, -$argumentCount)) : [];

        $state->stack[] = match ($type) {
            'Object' => new ScriptObject(),
            'Array' => new ScriptArray(...$args),
            default => throw new Exception('Unknown object type: '.$type),
        };
    }

    private function initObject(State $state): void
    {
        $propertiesCount = (int) array_pop($state->stack);
        $args = $propertiesCount > 0 ? array_splice($state->stack, -$propertiesCount * 2) : [];
        $properties = [];

        for ($i = 2 * $propertiesCount - 2; $i >= 0; $i -= 2) {
            $key = $args[$i];
            $value = $args[$i + 1];

            $properties[$key] = $value;
        }

        $state->stack[] = new ScriptObject($properties);
    }

    private function initArray(State $state): void
    {
        $size = (int) array_pop($state->stack);
        $values = $size > 0 ? array_reverse(array_splice($state->stack, -$size)) : [];

        // @todo use inlined array or ArrayObject?
        //$stack[] = new ArrayObject($values);
        $state->stack[] = $values;
    }

    private function setMember(State $state): void
    {
        $value = array_pop($state->stack);
        $propertyName = array_pop($state->stack);
        $scriptObject = array_pop($state->stack);

        if ($scriptObject === null) {
            return;
        }

        // Float that is an integer: use it as an int
        if (is_float($propertyName) && (int) $propertyName == $propertyName) {
            $propertyName = (int) $propertyName;
        }

        if (!is_int($propertyName)) {
            $propertyName = (string) $propertyName;
        }

        if ($scriptObject instanceof ScriptArray) {
            $scriptObject[$propertyName] = $value;
            return;
        }

        $scriptObject->$propertyName = $value;
    }

    private function toString(State $state): void
    {
        $index = count($state->stack) - 1;
        assert($index >= 0);
        // @phpstan-ignore assign.propertyType
        $state->stack[$index] = (string) ($state->stack[$index] ?? null);
    }

    private function toNumber(State $state): void
    {
        $index = count($state->stack) - 1;
        assert($index >= 0);
        // @phpstan-ignore assign.propertyType
        $state->stack[$index] = (float) ($state->stack[$index] ?? null);
    }

    private function callFunction(State $state): void
    {
        $functionName = (string) array_pop($state->stack);
        $argumentCount = (int) array_pop($state->stack);
        $args = $argumentCount > 0 ? array_reverse(array_splice($state->stack, -$argumentCount)) : [];

        $state->stack[] = match ($functionName) {
            'Boolean' => (bool) $args[0],
            'String' => (string) $args[0],
            'Number' => (float) $args[0],
            default => $this->callCustomFunction($state, $functionName, $args),
        };
    }

    /**
     * @param State $state
     * @param string $functionName
     * @param list<mixed> $args
     *
     * @return mixed
     * @throws Exception
     */
    private function callCustomFunction(State $state, string $functionName, array $args): mixed
    {
        if (!$this->allowFunctionCall) {
            return null;
        }

        $function = $state->functions[$functionName] ?? null;

        if (!$function) {
            throw new Exception('Unknown function: '.$functionName);
        }

        return $function(...$args);
    }
}
