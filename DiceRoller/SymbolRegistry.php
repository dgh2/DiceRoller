<?php

namespace DiceRoller;

use DiceRoller\OperatorRegistry\BinaryOperatorEntry;
use DiceRoller\OperatorRegistry\CloseArrayOperatorEntry;
use DiceRoller\OperatorRegistry\CloseGroupOperatorEntry;
use DiceRoller\OperatorRegistry\FunctionEntry;
use DiceRoller\OperatorRegistry\OpenArrayOperatorEntry;
use DiceRoller\OperatorRegistry\SeparatorEntry;
use DiceRoller\OperatorRegistry\Interfaces\HasOperation;
use DiceRoller\OperatorRegistry\NullaryOperatorEntry;
use DiceRoller\OperatorRegistry\OpenGroupOperatorEntry;
use DiceRoller\OperatorRegistry\UnaryOperatorEntry;

class SymbolRegistry
{
    /** @var NullaryOperatorEntry[] */
    private $nullaryOperatorEntries = array();
    /** @var UnaryOperatorEntry[] */
    private $unaryOperatorEntries = array();
    /** @var BinaryOperatorEntry[] */
    private $binaryOperatorEntries = array();
    /** @var FunctionEntry[] */
    private $functionEntries = array();
    /** @var OpenGroupOperatorEntry[] */
    private $openGroupOperatorEntries = array();
    /** @var CloseGroupOperatorEntry[] */
    private $closeGroupOperatorEntries = array();
    /** @var OpenArrayOperatorEntry[] */
    private $openArrayOperatorEntries = array();
    /** @var CloseArrayOperatorEntry[] */
    private $closeArrayOperatorEntries = array();
    /** @var SeparatorEntry[] */
    private $separatorEntries = array();

    /** @return string[] */
    public function getSymbolNames(): array
    {
        return array_unique(array_merge(
            $this->getNullaryOperatorNames(),
            $this->getUnaryOperatorNames(),
            $this->getBinaryOperatorNames(),
            $this->getFunctionNames(),
            $this->getOpenGroupOperatorNames(),
            $this->getCloseGroupOperatorNames(),
            $this->getOpenArrayOperatorNames(),
            $this->getCloseArrayOperatorNames(),
            $this->getSeparatorNames()));
    }

    /** @return string[] */
    public function getNullaryOperatorNames(): array
    {
        $registeredOperations = array();
        foreach ($this->nullaryOperatorEntries as $operatorEntry) {
            $registeredOperations[] = $operatorEntry->getName();
        }
        return $registeredOperations;
    }

    /** @return string[] */
    public function getUnaryOperatorNames(): array
    {
        $registeredOperations = array();
        foreach ($this->unaryOperatorEntries as $operatorEntry) {
            $registeredOperations[] = $operatorEntry->getName();
        }
        return $registeredOperations;
    }

    /** @return string[] */
    public function getBinaryOperatorNames(): array
    {
        $registeredOperations = array();
        foreach ($this->binaryOperatorEntries as $operatorEntry) {
            $registeredOperations[] = $operatorEntry->getName();
        }
        return $registeredOperations;
    }

    /** @return string[] */
    public function getFunctionNames(): array
    {
        $registeredFunctions = array();
        foreach ($this->functionEntries as $functionEntry) {
            $registeredFunctions[] = $functionEntry->getName();
        }
        return $registeredFunctions;
    }

    /** @return string[] */
    public function getOpenGroupOperatorNames(): array
    {
        $registeredGroupOperations = array();
        foreach ($this->openGroupOperatorEntries as $groupOperatorEntry) {
            $registeredGroupOperations[] = $groupOperatorEntry->getOpen();
        }
        return $registeredGroupOperations;
    }

    /** @return string[] */
    public function getCloseGroupOperatorNames(): array
    {
        $registeredGroupOperations = array();
        foreach ($this->closeGroupOperatorEntries as $groupOperatorEntry) {
            $registeredGroupOperations[] = $groupOperatorEntry->getClose();
        }
        return $registeredGroupOperations;
    }

    /** @return string[] */
    public function getOpenArrayOperatorNames(): array
    {
        $registeredArrayOperations = array();
        foreach ($this->openArrayOperatorEntries as $arrayOperatorEntry) {
            $registeredArrayOperations[] = $arrayOperatorEntry->getOpen();
        }
        return $registeredArrayOperations;
    }

    /** @return string[] */
    public function getCloseArrayOperatorNames(): array
    {
        $registeredArrayOperations = array();
        foreach ($this->closeArrayOperatorEntries as $arrayOperatorEntry) {
            $registeredArrayOperations[] = $arrayOperatorEntry->getClose();
        }
        return $registeredArrayOperations;
    }

    /** @return string[] */
    public function getSeparatorNames(): array
    {
        $registeredSeparators = array();
        foreach ($this->separatorEntries as $separatorEntry) {
            $registeredSeparators[] = $separatorEntry->getName();
        }
        return $registeredSeparators;
    }

    /**
     * @param $operator string
     * @return bool
     */
    public function isSymbolName($operator): bool
    {
        return in_array($operator, $this->getSymbolNames(), true);
    }

    /**
     * @param $operator string
     * @return bool
     */
    public function isNullaryOperatorName($operator): bool
    {
        return in_array($operator, $this->getNullaryOperatorNames(), true);
    }

    /**
     * @param $operator string
     * @return bool
     */
    public function isUnaryOperatorName($operator): bool
    {
        return in_array($operator, $this->getUnaryOperatorNames(), true);
    }

    /**
     * @param $operator string
     * @param $fix bool
     * @return bool
     */
    public function isUnaryOperator($operator, $fix): bool
    {
        foreach ($this->unaryOperatorEntries as $operatorEntry) {
            if ($operatorEntry->getName() == $operator && $operatorEntry->getFix() == $fix) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $operator string
     * @return bool
     */
    public function isBinaryOperatorName($operator): bool
    {
        return in_array($operator, $this->getBinaryOperatorNames(), true);
    }

    /**
     * @param $operator string
     * @return bool
     */
    public function isFunctionName($operator): bool
    {
        return in_array($operator, $this->getFunctionNames(), true);
    }

//    /**
//     * @param $operator string
//     * @return bool
//     */
//    public function isGroupOperatorName($operator): bool
//    {
//        return $this->isOpenGroupOperatorName($operator)
//            || $this->isClosingGroupOperatorName($operator);
//    }

    /**
     * @param $openOperator string
     * @param $closeOperator string
     * @return bool
     */
    public function isGroupOperatorNamePair($openOperator, $closeOperator): bool
    {
        foreach ($this->closeGroupOperatorEntries as $groupOperatorEntry) {
            if ($groupOperatorEntry->getClose() == $closeOperator && $groupOperatorEntry->getOpen() == $openOperator) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $operator string
     * @return bool
     */
    public function isOpenGroupOperatorName($operator): bool
    {
        return in_array($operator, $this->getOpenGroupOperatorNames(), true);
    }

    /**
     * @param $operator string
     * @return bool
     */
    public function isCloseGroupOperatorName($operator): bool
    {
        return in_array($operator, $this->getCloseGroupOperatorNames(), true);
    }

    /**
     * @param $operator string
     * @return bool
     */
    public function isOpenArrayOperatorName($operator): bool
    {
        return in_array($operator, $this->getOpenArrayOperatorNames(), true);
    }

    /**
     * @param $operator string
     * @return bool
     */
    public function isCloseArrayOperatorName($operator): bool
    {
        return in_array($operator, $this->getCloseArrayOperatorNames(), true);
    }

    /**
     * @param $operator string
     * @return bool
     */
    public function isSeparatorName($operator): bool
    {
        return in_array($operator, $this->getSeparatorNames(), true);
    }

    /**
     * @param $operation HasOperation
     * @return bool
     */
    public function register($operation): bool
    {
        if ($operation instanceof OpenGroupOperatorEntry) {
            $this->openGroupOperatorEntries[] = $operation;
        } elseif ($operation instanceof CloseGroupOperatorEntry) {
            $this->closeGroupOperatorEntries[] = $operation;
        } elseif ($operation instanceof OpenArrayOperatorEntry) {
            $this->openArrayOperatorEntries[] = $operation;
        } elseif ($operation instanceof CloseArrayOperatorEntry) {
            $this->closeArrayOperatorEntries[] = $operation;
        } elseif ($operation instanceof SeparatorEntry) {
            $this->separatorEntries[] = $operation;
        } elseif ($operation instanceof FunctionEntry) {
            $this->functionEntries[] = $operation;
        } elseif ($operation instanceof NullaryOperatorEntry) {
            $this->nullaryOperatorEntries[] = $operation;
        } elseif ($operation instanceof UnaryOperatorEntry) {
            $this->unaryOperatorEntries[] = $operation;
        } elseif ($operation instanceof BinaryOperatorEntry) {
            $this->binaryOperatorEntries[] = $operation;
        } else {
            return false;
        }
        return true;
    }

    /**
     * @param $operator string
     * @return NullaryOperatorEntry
     */
    public function getNullaryOperatorEntry($operator): ?NullaryOperatorEntry
    {
        foreach ($this->nullaryOperatorEntries as $operatorEntry) {
            if ($operatorEntry->getName() == $operator) {
                return $operatorEntry;
            }
        }
        return null;
    }

    /**
     * @param $operator string
     * @param $fix bool
     * @return UnaryOperatorEntry
     */
    public function getUnaryOperatorEntry($operator, $fix): ?UnaryOperatorEntry
    {
        foreach ($this->unaryOperatorEntries as $operatorEntry) {
            if ($operatorEntry->getName() == $operator && $operatorEntry->getFix() == $fix) {
                return $operatorEntry;
            }
        }
        return null;
    }

    /**
     * @param $operator string
     * @return BinaryOperatorEntry
     */
    public function getBinaryOperatorEntry($operator): ?BinaryOperatorEntry
    {
        foreach ($this->binaryOperatorEntries as $operatorEntry) {
            if ($operatorEntry->getName() == $operator) {
                return $operatorEntry;
            }
        }
        return null;
    }

    /**
     * @param $operator string
     * @return OpenGroupOperatorEntry
     */
    public function getOpenGroupOperatorEntry($operator): ?OpenGroupOperatorEntry
    {
        foreach ($this->openGroupOperatorEntries as $groupOperatorEntry) {
            if ($groupOperatorEntry->getOpen() == $operator) {
                return $groupOperatorEntry;
            }
        }
        return null;
    }

    /**
     * @param $operator string
     * @return CloseGroupOperatorEntry
     */
    public function getCloseGroupOperatorEntry($operator): ?CloseGroupOperatorEntry
    {
        foreach ($this->closeGroupOperatorEntries as $groupOperatorEntry) {
            if ($groupOperatorEntry->getClose() == $operator) {
                return $groupOperatorEntry;
            }
        }
        return null;
    }

    /**
     * @param $operator string
     * @return OpenArrayOperatorEntry
     */
    public function getOpenArrayOperatorEntry($operator): ?OpenArrayOperatorEntry
    {
        foreach ($this->openArrayOperatorEntries as $arrayOperatorEntry) {
            if ($arrayOperatorEntry->getOpen() == $operator) {
                return $arrayOperatorEntry;
            }
        }
        return null;
    }


    /**
     * @param $operator string
     * @return CloseArrayOperatorEntry
     */
    public function getCloseArrayOperatorEntry($operator): ?CloseArrayOperatorEntry
    {
        foreach ($this->closeArrayOperatorEntries as $arrayOperatorEntry) {
            if ($arrayOperatorEntry->getClose() == $operator) {
                return $arrayOperatorEntry;
            }
        }
        return null;
    }

    /**
     * @param $operator string
     * @return SeparatorEntry
     */
    public function getSeparatorEntry($operator): ?SeparatorEntry
    {
        foreach ($this->separatorEntries as $separatorEntry) {
            if ($separatorEntry->getName() == $operator) {
                return $separatorEntry;
            }
        }
        return null;
    }

    /**
     * @param $operator string
     * @return FunctionEntry
     */
    public function getFunctionEntry($operator): ?FunctionEntry
    {
        foreach ($this->functionEntries as $functionEntry) {
            if ($functionEntry->getName() == $operator) {
                return $functionEntry;
            }
        }
        return null;
    }
}