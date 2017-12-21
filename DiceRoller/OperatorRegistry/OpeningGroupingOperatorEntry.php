<?php

namespace DiceRoller\OperatorRegistry;

//stores the opening operator string value
class OpeningGroupingOperatorEntry extends UnaryOperatorEntry
{
    /**
     * @param $operator string
     */
    public function __construct($operator)
    {
        parent::__construct($operator, UnaryOperatorEntry::PREFIX, function ($operand) {
            return $operand;
        });
    }

    /** @return string */
    public function getOpen()
    {
        return parent::getName();
    }
}