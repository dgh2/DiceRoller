<?php

namespace DiceRoller\OperatorRegistry;

//stores the sentinel operator, which has the lowest precedence and is used as a placeholder
class SentinelOperatorEntry extends NullaryOperatorEntry
{
    public function __construct()
    {
        parent::__construct('sentinel', function () {
            return 'sentinel';
        });
    }

    public function getPrecedence(): int
    {
        return PHP_INT_MIN;
    }
}