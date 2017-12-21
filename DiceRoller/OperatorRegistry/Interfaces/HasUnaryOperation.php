<?php

namespace DiceRoller\OperatorRegistry\Interfaces;

interface HasUnaryOperation extends HasOperation
{
    public function call($operand);
}