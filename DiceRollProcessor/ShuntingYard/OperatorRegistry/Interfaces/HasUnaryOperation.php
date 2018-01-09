<?php

namespace ShuntingYard\OperatorRegistry\Interfaces;

require_once('HasOperation.php');

interface HasUnaryOperation extends HasOperation
{
    public function call($operand);
    public function getFix();
    public function isPrefix();
    public function isPostfix();
}