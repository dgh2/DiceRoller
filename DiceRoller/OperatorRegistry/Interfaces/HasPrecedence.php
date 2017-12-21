<?php

namespace DiceRoller\OperatorRegistry\Interfaces;

interface HasPrecedence
{
    public function getPrecedence(): int;
}