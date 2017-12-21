<?php

namespace DiceRoller\OperatorRegistry\Interfaces;

interface HasNullaryOperation extends HasOperation
{
    public function call();
}