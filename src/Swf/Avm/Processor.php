<?php

namespace Arakne\Swf\Avm;

use Arakne\Swf\Parser\Structure\Action\ActionRecord;
use Arakne\Swf\Parser\Structure\Action\Opcode;
use Arakne\Swf\Parser\Structure\Action\Type;
use Arakne\Swf\Parser\Structure\Action\Value;

use ArrayObject;

use Exception;

use function array_pop;
use function array_reverse;
use function array_splice;
use function count;
use function method_exists;

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
    ) {
    }

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
            Opcode::ActionConstantPool => $state->constants = $action->data,
            Opcode::ActionPush => array_push($state->stack, ...self::toPhpValues($state, ...$action->data)),
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
     * @no-named-arguments
     */
    public static function toPhpValues(State $state, Value ...$values): array
    {
        $parsed = [];

        foreach ($values as $value) {
            $parsed[] = match ($value->type) {
                Type::Constant8, Type::Constant16 => $state->constants[$value->value],
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
        $state->stack[$index] = $state->variables[$state->stack[$index]] ?? null;
    }

    private function getMember(State $state): void
    {
        $propertyName = array_pop($state->stack);
        $scriptObject = array_pop($state->stack);

        $state->stack[] = $scriptObject?->$propertyName ?? null;
    }

    private function callMethod(State $state): void
    {
        $methodName = array_pop($state->stack);
        $scriptObject = array_pop($state->stack);
        $argumentCount = array_pop($state->stack);
        $args = array_splice($state->stack, -$argumentCount);

        if (!$this->allowFunctionCall) {
            $state->stack[] = null;
            return;
        }

        if (!$scriptObject) {
            $state->stack[] = null;
            return;
        }

        if (!method_exists($scriptObject, $methodName)) {
            $state->stack[] = null;
            return;
        }

        $state->stack[] = $scriptObject->$methodName(...array_reverse($args));
    }

    private function newObject(State $state): void
    {
        $type = array_pop($state->stack);
        $argumentCount = array_pop($state->stack);
        $args = $argumentCount > 0 ? array_reverse(array_splice($state->stack, -$argumentCount)) : [];

        $state->stack[] = match ($type) {
            // @todo custom ScriptObject?
            'Object' => new ArrayObject($args, ArrayObject::ARRAY_AS_PROPS),
            'Array' => new ArrayObject($args),
            default => throw new Exception('Unknown object type: '.$type),
        };
    }

    private function initObject(State $state): void
    {
        $propertiesCount = array_pop($state->stack);
        $args = $propertiesCount > 0 ? array_splice($state->stack, -$propertiesCount * 2) : [];

        for ($i = 2 * $propertiesCount - 2; $i >= 0; $i -= 2) {
            $key = $args[$i];
            $value = $args[$i + 1];

            $properties[$key] = $value;
        }

        $state->stack[] = new ArrayObject($properties, ArrayObject::ARRAY_AS_PROPS);
    }

    private function initArray(State $state): void
    {
        $size = array_pop($state->stack);
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

        if ($scriptObject instanceof ArrayObject) {
            $scriptObject[$propertyName] = $value;
        } else {
            $scriptObject->$propertyName = $value;
        }
    }

    private function toString(State $state): void
    {
        $index = count($state->stack) - 1;
        $state->stack[$index] = (string) ($state->stack[$index] ?? null);
    }

    private function toNumber(State $state): void
    {
        $index = count($state->stack) - 1;
        $state->stack[$index] = (float) ($state->stack[$index] ?? null);
    }

    private function callFunction(State $state): void
    {
        $functionName = array_pop($state->stack);
        $argumentCount = array_pop($state->stack);
        $args = $argumentCount > 0 ? array_reverse(array_splice($state->stack, -$argumentCount)) : [];

        // @todo check for $allowFunctionCall. The following list is always allowed.
        $state->stack[] = match ($functionName) {
            'Boolean' => (bool) $args[0],
            'String' => (string) $args[0],
            'Number' => (float) $args[0], // @todo int ?
            default => throw new Exception('Unknown function: '.$functionName),
        };
    }
}
