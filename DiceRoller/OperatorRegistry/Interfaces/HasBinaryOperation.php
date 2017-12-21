<?php

namespace DiceRoller\OperatorRegistry\Interfaces;

interface HasBinaryOperation extends HasOperation
{
    public function call($operand1, $operand2);
}