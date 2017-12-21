<?php

namespace DiceRoller\OperatorRegistry;

//stores a separator operator
class SeparatorEntry extends BinaryOperatorEntry
{
    public function __construct($name)
    {
        parent::__construct($name, self::LEFT_ASSOCIATIVE, 0, function ($operand1, $operand2) {
            if ($operand1 instanceof Group) {
                $result = $operand1;
            } else {
                $result = new Group($operand1);
            }
            $result->contents[] = $operand2;
            return $result;
//            if (is_array($operand1)) {
//                $result = $operand1;
//                $result[] = $operand2;
//            } else {
//                if ($operand1 instanceof Group) {
//                    $result = $operand1;
//                } else {
//                    $result = new Group($operand1);
//                }
//                $result->contents[] = $operand2;
//            }
//            return $result;
        });
    }
}