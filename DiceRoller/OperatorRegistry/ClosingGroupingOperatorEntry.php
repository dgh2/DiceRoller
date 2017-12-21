<?php

namespace DiceRoller\OperatorRegistry;

//stores the closing operator string value
class ClosingGroupingOperatorEntry extends UnaryOperatorEntry
{
    /** @var OpeningGroupingOperatorEntry */
    private $openingOperator;

    /**
     * @param $operator string
     * @param $openingOperator OpeningGroupingOperatorEntry
     */
    public function __construct($operator, $openingOperator)
    {
        parent::__construct($operator, UnaryOperatorEntry::POSTFIX, function ($operand) {
//            echo "Calling ( on " . var_export($operand, true) . ' and returning ' . var_export(array($operand), true) . '<br/>';
            return $operand;
        });
        $this->openingOperator = $openingOperator;
    }

    /** @return string */
    public function getClose()
    {
        return parent::getName();
    }

    /** @return string */
    public function getOpen()
    {
        return $this->openingOperator->getOpen();
    }

    /** @return OpeningGroupingOperatorEntry */
    public function getOpenEntry()
    {
        return $this->openingOperator;
    }

    /** @return int */
    public function getPrecedence(): int
    {
        return PHP_INT_MIN;
    }
}