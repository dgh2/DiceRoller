<?php

namespace DiceRoller\OperatorRegistry;

class Group
{
    public $contents;

    public function __construct($initialContents)
    {
        $this->contents = array($initialContents);
    }
}