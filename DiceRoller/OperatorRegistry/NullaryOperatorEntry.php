<?php

namespace DiceRoller\OperatorRegistry;

use DiceRoller\OperatorRegistry\Interfaces\HasNullaryOperation;

//stores a nullary operator, e.g. PI => 3.14, and a callable that returns the replacement string
class NullaryOperatorEntry implements HasNullaryOperation
{
    private $name;
    private $operation;

    public function __construct($name, $operation)
    {
        $this->name = $name;
        $this->operation = $operation;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOperation(): callable
    {
        return $this->operation;
    }

    public function getPrecedence(): int
    {
        return PHP_INT_MAX;
    }

    public function call()
    {
        $operation = $this->operation;
        return is_callable($this->operation) ? $operation() : null;
    }

    public function getArity(): int
    {
        return 0;
    }
}