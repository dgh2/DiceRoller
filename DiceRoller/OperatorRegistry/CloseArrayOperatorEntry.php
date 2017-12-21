<?php

namespace DiceRoller\OperatorRegistry;

//stores the closing operator string value
class CloseArrayOperatorEntry extends UnaryOperatorEntry
{
    /** @var OpenArrayOperatorEntry */
    private $openOperator;

    /**
     * @param $operator string
     * @param $openOperator OpenArrayOperatorEntry
     */
    public function __construct($operator, $openOperator)
    {
        parent::__construct($operator, UnaryOperatorEntry::POSTFIX, function ($operand) {
//            echo "Calling $operator on " . var_export($operand, true) . ' and returning ' . var_export(array($operand), true) . '<br/>';
            return $operand;
        });
        $this->openOperator = $openOperator;
    }

    /** @return string */
    public function getClose()
    {
        return parent::getName();
    }

    /** @return string */
    public function getOpen()
    {
        return $this->openOperator->getOpen();
    }

    /** @return OpenArrayOperatorEntry */
    public function getOpenEntry()
    {
        return $this->openOperator;
    }

    /** @return int */
    public function getPrecedence(): int
    {
        return PHP_INT_MIN;
    }
}