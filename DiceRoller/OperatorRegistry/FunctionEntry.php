<?php

namespace DiceRoller\OperatorRegistry;

//stores a function operator and a callable to run on the operand array
class FunctionEntry extends UnaryOperatorEntry
{
    public function __construct($name, $operation)
    {
        parent::__construct($name, self::PREFIX, function ($operand) use ($operation) {
            if ($operand instanceof Group) {
                return $operation($operand->contents);
            } else {
                return $operation($operand);
            }
        });
    }
}