<?php

namespace DiceRoller\OperatorRegistry;

//stores the open operator string value
class OpenArrayOperatorEntry extends UnaryOperatorEntry
{
    /**
     * @param $operator string
     */
    public function __construct($operator)
    {
        parent::__construct($operator, UnaryOperatorEntry::PREFIX, function ($operand) {
            if ($operand instanceof Group) {
                $result = $operand->contents;
            } else {
                $result = array($operand);
            }
            return $result;
        });
    }

    /** @return string */
    public function getOpen()
    {
        return parent::getName();
    }
}