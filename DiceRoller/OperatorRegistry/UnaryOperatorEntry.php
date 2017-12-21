<?php

namespace DiceRoller\OperatorRegistry;

use DiceRoller\OperatorRegistry\Interfaces\HasUnaryOperation;

//stores a unary operator, if its prefix or postfix, and a callable to run on the operand
class UnaryOperatorEntry implements HasUnaryOperation
{
    public const PREFIX = true; // --5 => 5
    public const POSTFIX = false; // 5! => 120 (5*4*3*2)

    private $name;
    private $fix;
    private $operation;

    public function __construct($name, $fix/*, $precedence*/, $operation)
    {
        $this->name = $name;
        $this->fix = $fix;
        $this->operation = $operation;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFix(): bool
    {
        return $this->fix;
    }

    public function isPrefix(): bool
    {
        return $this->fix === self::PREFIX;
    }

    public function isPostfix(): bool
    {
        return $this->fix === self::POSTFIX;
    }

    public function getPrecedence(): int
    {
        return 5; // todo: test if PHP_INT_MAX can be used. It is used by NullaryOperator, so maybe use PHP_INT_MAX-1
    }

    public function getOperation(): callable
    {
        return $this->operation;
    }

    public function call($operand)
    {
        $operation = $this->operation;
        return is_callable($this->operation) ? $operation($operand) : null;
    }

    public function getArity(): int
    {
        return 1;
    }
}