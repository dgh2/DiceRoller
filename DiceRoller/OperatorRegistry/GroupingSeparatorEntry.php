<?php

namespace DiceRoller\OperatorRegistry;

//stores a grouping separator
class GroupingSeparatorEntry extends BinaryOperatorEntry
{
    public function __construct($name)
    {
        parent::__construct($name, self::LEFT_ASSOCIATIVE, 2, function ($operand1, $operand2) {
            if (is_array($operand1)) {
                $operand1[] = $operand2;
                return $operand1;
            } else {
                return array($operand1, $operand2);
            }
        });
    }
}