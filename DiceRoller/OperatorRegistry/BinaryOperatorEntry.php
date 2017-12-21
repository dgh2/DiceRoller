<?php

namespace DiceRoller\OperatorRegistry;

use DiceRoller\OperatorRegistry\Interfaces\HasBinaryOperation;

//stores a binary operator, if its left or right associative, and a callable to run on the operands
class BinaryOperatorEntry implements HasBinaryOperation
{
    public const LEFT_ASSOCIATIVE = true; // 2+3+4 => (2+3)+4
    public const RIGHT_ASSOCIATIVE = false; // 2^3^4 => 2^(3^4)

    private $name;
    private $fix;
    private $precedence;
    private $operation;

    public function __construct($name, $fix, $precedence, $operation)
    {
        $this->name = $name;
        $this->fix = $fix;
        $this->precedence = $precedence;
        $this->operation = $operation;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isLeftAssociative(): bool
    {
        return $this->fix === self::LEFT_ASSOCIATIVE;
    }

    public function isRightAssociative(): bool
    {
        return $this->fix === self::RIGHT_ASSOCIATIVE;
    }

    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    public function getOperation(): callable
    {
        return $this->operation;
    }

    public function call($operand1, $operand2)
    {
        $operation = $this->operation;
        return is_callable($this->operation) ? $operation($operand1, $operand2) : null;
    }

    public function getArity(): int
    {
        return 2;
    }
}