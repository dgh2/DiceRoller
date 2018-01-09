<?php

require_once('ShuntingYard/OperatorRegistry/UnaryOperatorEntry.php');
require_once('DiceRollSymbolRegistry.php');

use ShuntingYard\OperatorRegistry\UnaryOperatorEntry;
use ShuntingYard\SymbolRegistry;

class DiceRollHelp
{
    private $operationRegistry;

    public function __construct($operationRegistry)
    {
        if (!$operationRegistry instanceof SymbolRegistry) {
            throw new \InvalidArgumentException(var_export($operationRegistry, true) . " is not a SymbolRegistry");
        }
        $this->operationRegistry = $operationRegistry;
    }

    public function getHelpText($separator)
    {
        return implode($separator, $this->getHelpTextArray());
    }

    protected function getHelpArrayForOperatorNames($description, $inputOperatorNames): array
    {
        $help = [];
        if (!empty($inputOperatorNames)) {
            $help[] = '';
            $help[] = $description;
            $operatorNames = '';
            foreach ($inputOperatorNames as $operatorName) {
                if (empty($operatorNames)) {
                    $operatorNames .= $operatorName;
                } else {
                    $operatorNames .= ' ' . $operatorName;
                }
            }
            $help[] = $operatorNames;
        }
        return $help;
    }

    protected function getHelpTextArray()
    {
        $help = [];
        $help[] = 'Dice Roller Help:';
        $help = array_merge($help, self::getHelpArrayForOperatorNames(
            'The following direct replacement shortcuts are supported:',
            $this->operationRegistry->getNullaryOperatorNames()));
        $help = array_merge($help, self::getHelpArrayForOperatorNames(
            'The following functions are supported, immediately before a set of parenthesis containing the function arguments:',
            $this->operationRegistry->getFunctionNames()));
        $help = array_merge($help, self::getHelpArrayForOperatorNames(
            'The following unary operators are supported, immediately before their argument:',
            $this->operationRegistry->getUnaryOperatorNames(UnaryOperatorEntry::PREFIX)));
        $help = array_merge($help, self::getHelpArrayForOperatorNames(
            'The following unary operators are supported, immediately after their argument:',
            $this->operationRegistry->getUnaryOperatorNames(UnaryOperatorEntry::POSTFIX)));
        $help = array_merge($help, self::getHelpArrayForOperatorNames(
            'The following binary operators are supported, immediately between their arguments:',
            $this->operationRegistry->getBinaryOperatorNames()));
        $help = array_merge($help, self::getHelpArrayForOperatorNames(
            'The following grouping operators are supported to designate arrays:',
            array_merge($this->operationRegistry->getOpenArrayOperatorNames(), 
                $this->operationRegistry->getCloseArrayOperatorNames())));
        $help = array_merge($help, self::getHelpArrayForOperatorNames(
            'The following grouping operators are supported to designate groups such as function arguments and arrays:',
                array_merge($this->operationRegistry->getOpenGroupOperatorNames(), 
                    $this->operationRegistry->getCloseGroupOperatorNames())));
        $help = array_merge($help, self::getHelpArrayForOperatorNames(
            'The following operators are supported as separators for the grouping and array operators:',
            $this->operationRegistry->getSeparatorNames()));
        return $help;
    }
}