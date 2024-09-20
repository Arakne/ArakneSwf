<?php

namespace Arakne\Swf\Avm;

/**
 * Store the current state of the AVM.
 */
final class State
{
    /**
     * Constants pool.
     *
     * @var array<int, string>
     */
    public array $constants = [];

    /**
     * The execution stack.
     *
     * @var list<mixed>
     */
    public array $stack = [];

    /**
     * Current global variables.
     *
     * @var array<string, mixed>
     */
    public array $variables = [];

    /**
     * Global functions.
     *
     * @var array<string, callable>
     */
    public array $functions = [];
}
