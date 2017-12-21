<?php

namespace DiceRoller\OperatorRegistry\Interfaces;

interface HasOperation extends HasName, HasPrecedence, HasArity
{
    public function getOperation(): callable;
}