<?php

namespace DiceRoller;

use DiceRoller\OperatorRegistry\BinaryOperatorEntry;
use DiceRoller\OperatorRegistry\CloseArrayOperatorEntry;
use DiceRoller\OperatorRegistry\CloseGroupOperatorEntry;
use DiceRoller\OperatorRegistry\FunctionEntry;
use DiceRoller\OperatorRegistry\Interfaces\HasOperation;
use DiceRoller\OperatorRegistry\NullaryOperatorEntry;
use DiceRoller\OperatorRegistry\OpenArrayOperatorEntry;
use DiceRoller\OperatorRegistry\OpenGroupOperatorEntry;
use DiceRoller\OperatorRegistry\UnaryOperatorEntry;
use DiceRoller\OperatorRegistry\SentinelOperatorEntry;

class ShuntingYardProcessor
{
    private $operationRegistry;

    private $postfixTokenArray = array();
    private $log = array();
    private $devlog = array();
    private $error = null;
    private $processingResult = array();

    public function __construct($symbolRegistry)
    {
        if (!$symbolRegistry instanceof SymbolRegistry) {
            throw new \InvalidArgumentException("$symbolRegistry is not an OperationRegistry");
        }
        $this->operationRegistry = $symbolRegistry;
    }

    public function reset()
    {
        $this->postfixTokenArray = array();
        $this->log = array();
        $this->devlog = array();
        $this->error = null;
        $this->processingResult = array();
    }

    private function shouldExpectPrefixUnary($lastToken): bool
    {
        if ($lastToken == null) {
            return true;
        }
        $wasSymbol = $this->operationRegistry->isSymbolName($lastToken);
        $wasCloseOperator = $this->operationRegistry->isCloseGroupOperatorName($lastToken)
            || $this->operationRegistry->isCloseArrayOperatorName($lastToken);
        $wasNullaryOperator = $this->operationRegistry->isNullaryOperatorName($lastToken);
        if ($wasSymbol && !$wasCloseOperator && !$wasNullaryOperator) {
            return true;
        }
        return false;
    }

    private function shouldExpectPostfixUnary($nextToken): bool
    {
        $isNullaryOperator = $this->operationRegistry->isNullaryOperatorName($nextToken);
        return $nextToken == null || ($this->operationRegistry->isSymbolName($nextToken) && !$isNullaryOperator);
    }

    /**
     * @param $currentOperator HasOperation
     * @param $previousOperator HasOperation
     * @return bool
     */
    private function shouldProcessOperator($currentOperator, $previousOperator): bool
    {
        if (!$currentOperator instanceof CloseGroupOperatorEntry && $previousOperator instanceof OpenGroupOperatorEntry) {
            $this->devlog[] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
                "Should not process '" . $previousOperator->getOpen() . "' for '" . $currentOperator->getName() . "'";
            return false;
        }
        if (!$currentOperator instanceof CloseArrayOperatorEntry && $previousOperator instanceof OpenArrayOperatorEntry) {
            $this->devlog[] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
                "Should not process '" . $previousOperator->getOpen() . "' for '" . $currentOperator->getName() . "'";
            return false;
        }
//        if ($currentOperator instanceof OpenGroupOperatorEntry && $previousOperator instanceof CloseGroupOperatorEntry) {
//            $this->devlog[] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
//                "Should not process '" . $previousOperator->getClose() . "' for '" . $currentOperator->getOpen() . "'";
//            return false;
//        }
//        if ($currentOperator instanceof CloseGroupOperatorEntry && $previousOperator instanceof OpenGroupOperatorEntry) {
//            $this->devlog[] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
//                "Should not process '" . $previousOperator->getOpen() . "' for '" . $currentOperator->getClose() . "'";
//            return false;
//        }
//        if ($currentOperator instanceof UnaryOperatorEntry && $previousOperator instanceof BinaryOperatorEntry
//            && !($currentOperator instanceof CloseGroupOperatorEntry && $previousOperator instanceof SeparatorEntry)) {
//            $this->devlog[] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
//                "Should not process '" . $previousOperator->getName() . "' for '" . $currentOperator->getName() . "'";
//            return false;
//        }

        $currentOperatorPrecedence = $currentOperator->getPrecedence();
        $previousOperatorPrecedence = $previousOperator->getPrecedence();
        if ($previousOperatorPrecedence > $currentOperatorPrecedence) {
            $this->devlog[] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
                "'" . $currentOperator->getName() . "' has a lower precedence than '" . $previousOperator->getName() .
                "', pop and call '" . $previousOperator->getName() . "'";
            return true;
        }
        if ($currentOperator instanceof BinaryOperatorEntry
            && $previousOperatorPrecedence == $currentOperatorPrecedence && $currentOperator->isLeftAssociative()) {
            $this->devlog[] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
                "'" . $currentOperator->getName() . "' has the same precedence as '" . $previousOperator->getName() .
                "' and is left associative, pop and call '" . $previousOperator->getName() . "'";
            return true;
        }
        $this->devlog[] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
            "Should not process '" . $previousOperator->getName() . "' for '" . $currentOperator->getName() . "'";
        return false;
    }

    /**
     * @param $command string
     * @return string
     */
    public function preprocessCommand($command)
    {
        $matchFn = function ($matches) {
            return $matches[1] . '*' . $matches[2];
        };
        $processedCommand = preg_replace_callback('/(\d)(\()/', $matchFn, $command);
        $processedCommand = preg_replace_callback('/(\))(\d)/', $matchFn, $processedCommand);
        $processedCommand = preg_replace_callback('/(\))(\()/', $matchFn, $processedCommand);
        return $processedCommand;
    }

    /**
     * @param $command string
     * @return array
     */
    public function tokenizeCommand($command)
    {
        $commandSplitRegex = "";
        foreach ($this->operationRegistry->getSymbolNames() as $registeredSymbol) {
            $commandSplitRegex .= '|' . preg_quote($registeredSymbol, '/');
        }
        $commandSplitRegex = '#(' . substr($commandSplitRegex, 1) . ')#';
        return preg_split($commandSplitRegex, $command, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    public function processRawCommand($command)
    {
        if ($command == InputHandler::HELP_COMMAND) {
            $this->processingResult = $this->getHelpText('<br/>');
        } else {
            $this->process($this->tokenizeCommand($this->preprocessCommand($command)));
        }
    }

    public function getHelpText($separator) {
        return implode($separator, $this->getHelpTextArray());
    }

    public function getHelpTextArray()
    {
        $help = [];
        $help[] = 'Dice Roller Help Text:';
        $help[] = '';
        $help[] = 'The following direct replacement shortcuts are supported:';
        foreach ($this->operationRegistry->getNullaryOperatorNames() as $nullaryOperatorName) {
            $help[] = $nullaryOperatorName;
        }
        $help[] = '';
        $help[] = 'The following functions are supported in the format function(arguments):';
        foreach ($this->operationRegistry->getFunctionNames() as $functionName) {
            $help[] = $functionName;
        }
        $help[] = '';
        $help[] = 'The following unary operators are supported, immediately following their argument:';
        foreach ($this->operationRegistry->getUnaryOperatorNames() as $unaryOperatorName) {
            $help[] = $unaryOperatorName;
        }
        $help[] = '';
        $help[] = 'The following binary operators are supported, immediately between their arguments:';
        foreach ($this->operationRegistry->getBinaryOperatorNames() as $binaryOperatorName) {
            $help[] = $binaryOperatorName;
        }
        $help[] = '';
        $help[] = 'The following grouping operators are supported to designate arrays:';
        foreach ($this->operationRegistry->getCloseArrayOperatorNames() as $closeArrayOperatorName) {
            $help[] = $this->operationRegistry->getCloseArrayOperatorEntry($closeArrayOperatorName)->getOpen() . $closeArrayOperatorName;
        }
        $help[] = '';
        $help[] = 'The following grouping operators are supported to designate groups:';
        foreach ($this->operationRegistry->getCloseGroupOperatorNames() as $closeGroupOperatorName) {
            $help[] = $this->operationRegistry->getCloseGroupOperatorEntry($closeGroupOperatorName)->getOpen() . $closeGroupOperatorName;
        }
        $help[] = '';
        $help[] = 'The following operators are supported as separators for the grouping operators:';
        foreach ($this->operationRegistry->getSeparatorNames() as $separatorName) {
            $help[] = $separatorName;
        }
        return $help;
    }

    public function process($infixTokenArray)
    {
        try {
            /** @var $operationStack HasOperation[] */
            $operationStack = array(new SentinelOperatorEntry());
            $lastToken = null;
            for ($i = 0; $i < count($infixTokenArray); ++$i) {
                $token = $infixTokenArray[$i];
                $nextToken = ($i + 1 >= count($infixTokenArray) ? null : $infixTokenArray[$i + 1]);
                //            //$this->devlog[] = "Processing infix token: $token";
                if ($this->operationRegistry->isSymbolName($token)) {
                    $operation = null;
                    if ($this->operationRegistry->isFunctionName($token)) {
                        $operation = $this->operationRegistry->getFunctionEntry($token);
                        //                    //$this->devlog[] = "&nbsp;&nbsp;Found function operator '$token'";
                    } elseif ($this->operationRegistry->isOpenGroupOperatorName($token)) {
                        $operation = $this->operationRegistry->getOpenGroupOperatorEntry($token);
                        //                    //$this->devlog[] = "&nbsp;&nbsp;Found open group operator '$token'";
                    } elseif ($this->operationRegistry->isCloseGroupOperatorName($token)) {
                        $operation = $this->operationRegistry->getCloseGroupOperatorEntry($token);
                        //                    //$this->devlog[] = "&nbsp;&nbsp;Found closing group operator '$token'";
                    } elseif ($this->operationRegistry->isOpenArrayOperatorName($token)) {
                        $operation = $this->operationRegistry->getOpenArrayOperatorEntry($token);
                        //                    //$this->devlog[] = "&nbsp;&nbsp;Found open group operator '$token'";
                    } elseif ($this->operationRegistry->isCloseArrayOperatorName($token)) {
                        $operation = $this->operationRegistry->getCloseArrayOperatorEntry($token);
                        //                    //$this->devlog[] = "&nbsp;&nbsp;Found closing group operator '$token'";
                    } elseif ($this->operationRegistry->isSeparatorName($token)) {
                        $operation = $this->operationRegistry->getSeparatorEntry($token);
                        //                    //$this->devlog[] = "&nbsp;&nbsp;Found group separator '$token'";
                    } elseif ($this->operationRegistry->isNullaryOperatorName($token)) {
                        $operation = $this->operationRegistry->getNullaryOperatorEntry($token);
                        //                    //$this->devlog[] = "&nbsp;&nbsp;Found nullary operator '$token'";
                    } elseif ($this->shouldExpectPrefixUnary($lastToken) && $this->operationRegistry->isUnaryOperator($token, UnaryOperatorEntry::PREFIX)) {
                        $operation = $this->operationRegistry->getUnaryOperatorEntry($token, UnaryOperatorEntry::PREFIX);
                        //                    //$this->devlog[] = "&nbsp;&nbsp;Found unary (prefix) operator '$token'";
                    } elseif ($this->shouldExpectPostfixUnary($nextToken) && $this->operationRegistry->isUnaryOperator($token, UnaryOperatorEntry::POSTFIX)) {
                        $operation = $this->operationRegistry->getUnaryOperatorEntry($token, UnaryOperatorEntry::POSTFIX);
                        //                    //$this->devlog[] = "&nbsp;&nbsp;Found unary (postfix) operator '$token'";
                    } else {
                        $operation = $this->operationRegistry->getBinaryOperatorEntry($token);
                        //                    //$this->devlog[] = "&nbsp;&nbsp;Found binary operator '$token'";
                    }

                    if ($operation == null) {
                        $this->error = "No operation found for '$token'<br/>";
                        $this->devlog[] = $this->error;
                        return;
                    }

                    $groupOpenFound = false;
                    $arrayOpenFound = false;
                    while ($this->success() && !empty($operationStack) && $this->shouldProcessOperator($operation, current(array_slice($operationStack, -1)))) {
                        $popped = array_pop($operationStack);
                        if ($popped instanceof FunctionEntry) {
                            $this->error = "No arguments passed to function: " . $popped->getName() . " (Function calls require parenthesis)";
                            $this->devlog[] = $this->error;
                            return;
                        }
                        $result = $this->callOperation($popped);
                        $this->postfixTokenArray[] = $result;
                        $this->devlog[] = "&nbsp;&nbsp;Pushing " . var_export($result, true) . " onto postfixTokenArray";
                        $groupOpenFound = $operation instanceof CloseGroupOperatorEntry && $operation->getOpen() == $popped->getName();
                        $arrayOpenFound = $operation instanceof CloseArrayOperatorEntry && $operation->getOpen() == $popped->getName();
                        if ($groupOpenFound) {
                            //Handle functions specially so they are only processed before matched open parenthesis
                            if (current(array_slice($operationStack, -1)) instanceof FunctionEntry) {
                                $result = $this->callOperation(array_pop($operationStack));
                                $this->postfixTokenArray[] = $result;
                                $this->devlog[] = "&nbsp;&nbsp;Pushing " . var_export($result, true) . " onto postfixTokenArray";
                            }
                            break;
                        }
                        if ($arrayOpenFound) {
                            break;
                        }
                    }
                    if ($operation instanceof CloseGroupOperatorEntry && !$groupOpenFound) {
                        $this->error = "Mismatched group operator: " . $operation->getClose();
                        $this->devlog[] = $this->error;
                        return;
                    }
                    if ($operation instanceof CloseArrayOperatorEntry && !$arrayOpenFound) {
                        $this->error = "Mismatched array operator: " . $operation->getClose();
                        $this->devlog[] = $this->error;
                        return;
                    }
                    if ($this->success() && !$operation instanceof CloseGroupOperatorEntry && !$operation instanceof CloseArrayOperatorEntry) {
                        $operationStack[] = $operation;
                        $this->devlog[] = "&nbsp;&nbsp;Pushing '$token' onto operationStack";
                    }
                } elseif (!is_array($token) && strval(intval($token)) == strval($token)) {
                    $this->postfixTokenArray[] = intval($token);
                    $this->devlog[] = "&nbsp;&nbsp;Pushing " . intval($token) . " onto postfixTokenArray";
                } elseif (!is_array($token) && strval(floatval($token)) == strval($token)) {
                    $this->postfixTokenArray[] = floatval($token);
                    $this->devlog[] = "&nbsp;&nbsp;Pushing " . floatval($token) . " onto postfixTokenArray";
                } else {
                    $this->error = "Unrecognized token: " . var_export($token, true);
                }
                $lastToken = $token;
            }
            if ($this->success()) {
                $this->devlog[] = "Reached end of postfixTokenArray, process all operators on the operationStack";
            }
            while ($this->success() && count($operationStack) > 1) {
                $operation = array_pop($operationStack);
                $operatorName = $operation->getName();
                $this->devlog[] = "Popping '$operatorName' from operationStack";
                if ($this->operationRegistry->isOpenGroupOperatorName($operatorName)) {
                    $this->error = "Mismatched group operator: $operatorName";
                    $this->devlog[] = $this->error;
                    break;
                }
                if ($this->operationRegistry->isOpenArrayOperatorName($operatorName)) {
                    $this->error = "Mismatched array operator: $operatorName";
                    $this->devlog[] = $this->error;
                    break;
                }
                if ($this->operationRegistry->isFunctionName($operatorName)) {
                    $this->error = "No arguments passed to function: $operatorName" . " (Function calls require parenthesis)";
                    $this->devlog[] = $this->error;
                    break;
                }

                $result = $this->callOperation($operation);
                $this->postfixTokenArray[] = $result;
                $this->devlog[] = "Pushing " . var_export($result, true) . " onto postfixTokenArray";
            }
            if ($this->success()) {
                if (empty($this->postfixTokenArray)) {
//                    $this->error = "Invalid input: {" . implode(',', $infixTokenArray) . "}";
                    $this->error = "Invalid input";
                } elseif (count($this->postfixTokenArray) > 1) {
//                    $this->error = "Invalid input: {" . implode(',', $infixTokenArray) . "} - Too few operations provided";
                    $this->error = "Too few operations provided";
                }
                if ($this->success()) {
                    $this->processingResult = array_pop($this->postfixTokenArray);
                    $this->devlog[] = "Found result: " . var_export($this->processingResult, true);
                } else {
                    $this->devlog[] = $this->error;
//                    $this->devlog[] = "Final state: {" . implode(',', $this->postfixTokenArray) . "}";
//                    $this->devlog[] = var_export($this->postfixTokenArray, true);
                    $this->devlog[] = "Final state: " . var_export($this->postfixTokenArray, true);
                }
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->devlog[] = $this->error;
            $this->devlog[] = var_export($this->postfixTokenArray, true);
        }
    }

    private function callOperation($operation)
    {
        //todo: implement dynamic operation arity
        $result = null;
        if ($operation instanceof NullaryOperatorEntry) {
            $result = $operation->call();
            $this->devlog[] = "&nbsp;&nbsp;&nbsp;&nbsp;" .
                "Call to '" . $operation->getName() .
                "' yielded " . var_export($result, true);
        } elseif ($operation instanceof UnaryOperatorEntry) {
            if (count($this->postfixTokenArray) < 1) {
                $this->error = "No operand provided for operator: " . $operation->getName();
                return null;
            }
            $operand = array_pop($this->postfixTokenArray);
//            if ($operation instanceof FunctionEntry && !$operand instanceof Group) {
//                $this->error = "No arguments passed to function: " . $operation->getName() . " (Function calls require parenthesis)";
//                $this->devlog[] = $this->error;
//                return null;
//            }
            $result = $operation->call($operand);
            $this->devlog[] = "&nbsp;&nbsp;&nbsp;&nbsp;" .
                "Call to '" . $operation->getName() . "' with " . var_export($operand, true) .
                " yielded " . var_export($result, true);
        } elseif ($operation instanceof BinaryOperatorEntry) {
            if (count($this->postfixTokenArray) < 2) {
                $this->error = "Too few operands provided for operator: " . $operation->getName();
                return null;
            }
            $operand2 = array_pop($this->postfixTokenArray);
            $operand1 = array_pop($this->postfixTokenArray);
            $result = $operation->call($operand1, $operand2);
            $this->devlog[] = "&nbsp;&nbsp;&nbsp;&nbsp;" .
                "Call to '" . $operation->getName() . "' with " .
                var_export($operand1, true) . " and " . var_export($operand2, true) .
                " yielded " . var_export($result, true);
        }
        return $result;
    }

    public function success()
    {
        return !isset($this->error);
    }

    public function getError()
    {
        return $this->error;
    }

    public function getDevlog(): array
    {
        return $this->devlog;
    }

    public function getLog(): array
    {
        return $this->log;
    }

    public function getPostfixTokenArray(): array
    {
        return $this->postfixTokenArray;
    }

    public function getProcessingResult()
    {
        return $this->processingResult;
    }
}