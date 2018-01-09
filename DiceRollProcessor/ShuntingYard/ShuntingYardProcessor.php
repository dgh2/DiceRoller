<?php

namespace ShuntingYard;

require_once('OperatorRegistry/BinaryOperatorEntry.php');
require_once('OperatorRegistry/CloseArrayOperatorEntry.php');
require_once('OperatorRegistry/CloseGroupOperatorEntry.php');
require_once('OperatorRegistry/FunctionEntry.php');
require_once('OperatorRegistry/Interfaces/HasBinaryOperation.php');
require_once('OperatorRegistry/Interfaces/HasOperation.php');
require_once('OperatorRegistry/Interfaces/HasGroup.php');
require_once('OperatorRegistry/Interfaces/HasName.php');
require_once('OperatorRegistry/Interfaces/HasNullaryOperation.php');
require_once('OperatorRegistry/Interfaces/HasPrecedence.php');
require_once('OperatorRegistry/Interfaces/HasUnaryOperation.php');
require_once('OperatorRegistry/NullaryOperatorEntry.php');
require_once('OperatorRegistry/OpenArrayOperatorEntry.php');
require_once('OperatorRegistry/OpenGroupOperatorEntry.php');
require_once('OperatorRegistry/UnaryOperatorEntry.php');

use ShuntingYard\OperatorRegistry\BinaryOperatorEntry;
use ShuntingYard\OperatorRegistry\CloseArrayOperatorEntry;
use ShuntingYard\OperatorRegistry\CloseGroupOperatorEntry;
use ShuntingYard\OperatorRegistry\FunctionEntry;
use ShuntingYard\OperatorRegistry\Interfaces\HasBinaryOperation;
use ShuntingYard\OperatorRegistry\Interfaces\HasOperation;
use ShuntingYard\OperatorRegistry\Interfaces\HasGroup;
use ShuntingYard\OperatorRegistry\Interfaces\HasName;
use ShuntingYard\OperatorRegistry\Interfaces\HasNullaryOperation;
use ShuntingYard\OperatorRegistry\Interfaces\HasPrecedence;
use ShuntingYard\OperatorRegistry\Interfaces\HasUnaryOperation;
use ShuntingYard\OperatorRegistry\NullaryOperatorEntry;
use ShuntingYard\OperatorRegistry\OpenArrayOperatorEntry;
use ShuntingYard\OperatorRegistry\OpenGroupOperatorEntry;
use ShuntingYard\OperatorRegistry\UnaryOperatorEntry;

class ShuntingYardProcessor
{
    private $operationRegistry;

    private $postfixTokenArray = [];
    private $log = [];
    private $debugLog = [];
    private $error = null;
    private $processingResult = [];

    public function __construct($symbolRegistry)
    {
        if (!$symbolRegistry instanceof SymbolRegistry) {
            throw new \InvalidArgumentException(var_export($symbolRegistry, true) . ' is not a SymbolRegistry');
        }
        $this->operationRegistry = $symbolRegistry;
    }

    public function reset()
    {
        $this->postfixTokenArray = [];
        $this->log = [];
        $this->debugLog = [];
        $this->error = null;
        $this->processingResult = [];
    }

    /**7
     * @param $currentOperator HasOperation
     * @param $previousOperator HasOperation
     * @return bool
     */
    private function shouldProcessOperator($currentOperator, $previousOperator): bool
    {
        if (!isset($previousOperator)) {
            $this->debugLog[] = 'Should not process null for ' . $currentOperator->getName() . '';
            return false;
        }
        if (!$currentOperator instanceof CloseGroupOperatorEntry && $previousOperator instanceof OpenGroupOperatorEntry) {
            $this->debugLog[] = 'Should not process ' . $previousOperator->getOpen() . ' for ' . $currentOperator->getName() . '';
            return false;
        }
        if (!$currentOperator instanceof CloseArrayOperatorEntry && $previousOperator instanceof OpenArrayOperatorEntry) {
            $this->debugLog[] = 'Should not process ' . $previousOperator->getOpen() . ' for ' . $currentOperator->getName() . '';
            return false;
        }

        $currentOperatorPrecedence = $currentOperator->getPrecedence();
        $previousOperatorPrecedence = (!isset($previousOperator) || !$previousOperator instanceof HasPrecedence)
            ? PHP_INT_MIN : $previousOperator->getPrecedence();
        if ($previousOperatorPrecedence > $currentOperatorPrecedence) {
            $this->debugLog[] = '' . $currentOperator->getName() . ' has a lower precedence than ' . $previousOperator->getName() .
                ', pop and call ' . $previousOperator->getName() . '';
            return true;
        }
        if ($previousOperatorPrecedence == $currentOperatorPrecedence) {
            if ($currentOperator instanceof BinaryOperatorEntry && $currentOperator->isLeftAssociative()) {
                $this->debugLog[] = '' . $currentOperator->getName() . ' has the same precedence as ' . $previousOperator->getName() .
                    ' and is left associative, pop and call ' . $previousOperator->getName() . '';
                return true;
            }
            if ($currentOperator instanceof UnaryOperatorEntry && $currentOperator->isPostfix()) {
                $this->debugLog[] = '' . $currentOperator->getName() . ' has the same precedence as ' . $previousOperator->getName() .
                    ' and is postfix, pop and call ' . $previousOperator->getName() . '';
                return true;
            }
        }
        $this->debugLog[] = 'Should not process ' . $previousOperator->getName() . ' for ' . $currentOperator->getName() . '';
        return false;
    }

    /**
     * @param $command string
     * @return string
     */
    protected function preprocessCommand($command)
    {
        $matchFn = function ($matches) {
            return $matches[1] . '*' . $matches[2];
        };
        $processedCommand = preg_replace_callback('/(\d)([\[\(])/', $matchFn, $command);
        $processedCommand = preg_replace_callback('/([\]\)])(\d)/', $matchFn, $processedCommand);
        $processedCommand = preg_replace_callback('/([\]\)])([\[\(])/', $matchFn, $processedCommand);
        return $processedCommand;
    }

    /**
     * @param $command string
     * @return array
     */
    protected function tokenizeCommand($command)
    {
        $commandSplitRegex = '';
        foreach ($this->operationRegistry->getSymbolNames() as $registeredSymbol) {
            $commandSplitRegex .= '|' . preg_quote($registeredSymbol, '/');
        }
        $commandSplitRegex = '#(' . substr($commandSplitRegex, 1) . ')#';
        $tokenizedCommand = preg_split($commandSplitRegex, $command, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        return $tokenizedCommand;
    }

    public function processCommand($command)
    {
        $this->debugLog[] = 'Processing command: ' . $command;
        $this->process($this->identifyTokens($this->tokenizeCommand($this->preprocessCommand($command))));
    }

    private function getOperation($previous, $token)
    {
        $operation = $token;
        if ($this->operationRegistry->isSymbolName($token)) {
            $expectNullaryOrPrefixUnary = $previous == null
                || $previous instanceof BinaryOperatorEntry
                || ($previous instanceof UnaryOperatorEntry && $previous->isPrefix());
            $expectBinaryOrPostfixUnary = $previous != null
                && (!$previous instanceof HasOperation
                    || $previous instanceof NullaryOperatorEntry
                    || ($previous instanceof UnaryOperatorEntry && $previous->isPostfix()));
            if ($this->operationRegistry->isOpenArrayOperatorName($token)) {
                $operation = $this->operationRegistry->getOpenArrayOperatorEntry($token);
            } elseif ($this->operationRegistry->isCloseArrayOperatorName($token)) {
                $operation = $this->operationRegistry->getCloseArrayOperatorEntry($token);
            } elseif ($this->operationRegistry->isOpenGroupOperatorName($token)) {
                $operation = $this->operationRegistry->getOpenGroupOperatorEntry($token);
            } elseif ($this->operationRegistry->isCloseGroupOperatorName($token)) {
                $operation = $this->operationRegistry->getCloseGroupOperatorEntry($token);
            } elseif ($this->operationRegistry->isFunctionName($token)) {
                $operation = $this->operationRegistry->getFunctionEntry($token);
            } elseif ($this->operationRegistry->isSeparatorName($token)) {
                $operation = $this->operationRegistry->getSeparatorEntry($token);
            } elseif ($expectNullaryOrPrefixUnary && $this->operationRegistry->isNullaryOperatorName($token)) {
                $operation = $this->operationRegistry->getNullaryOperatorEntry($token);
            } elseif (($expectNullaryOrPrefixUnary) && $this->operationRegistry->isUnaryOperator($token, UnaryOperatorEntry::PREFIX)) {
                $operation = $this->operationRegistry->getUnaryOperatorEntry($token, UnaryOperatorEntry::PREFIX);
            } elseif ($expectBinaryOrPostfixUnary && $this->operationRegistry->isBinaryOperatorName($token)) {
                $operation = $this->operationRegistry->getBinaryOperatorEntry($token);
            } elseif ($expectBinaryOrPostfixUnary && $this->operationRegistry->isUnaryOperator($token, UnaryOperatorEntry::POSTFIX)) {
                $operation = $this->operationRegistry->getUnaryOperatorEntry($token, UnaryOperatorEntry::POSTFIX);
            }
        }
        return $operation;
    }

    private function identifyTokens($tokenizedCommand)
    {
        $identifiedTokens = [];
        foreach ($tokenizedCommand as $token) {
            if (empty($identifiedTokens)) {
                $identifiedTokens[] = $this->getOperation(null, $token);
            } else {
                $identifiedTokens[] = $this->getOperation(end($identifiedTokens), $token);
            }
        }
        return $identifiedTokens;
    }

    protected function process($infixOperationArray)
    {
        try {
            /** @var $operationStack HasOperation[] */
            $lastToken = null;
            $operationStack = [$lastToken];
            for ($i = 0; $i < count($infixOperationArray); ++$i) {
                $token = $infixOperationArray[$i];
                $expectNullaryOrPrefixUnary = $lastToken == null
                    || $lastToken instanceof HasBinaryOperation
                    || ($lastToken instanceof HasUnaryOperation && $lastToken->isPrefix());
                $expectBinaryOrPostfixUnary = $lastToken != null
                    && (!$lastToken instanceof HasOperation
                        || $lastToken instanceof HasNullaryOperation
                        || ($lastToken instanceof HasUnaryOperation && $lastToken->isPostfix()));
                $emptyClosing = ($token instanceof CloseGroupOperatorEntry
                        && $lastToken instanceof OpenGroupOperatorEntry
                        && $token->getOpen() == $lastToken->getOpen())
                    || ($token instanceof CloseArrayOperatorEntry
                        && $lastToken instanceof OpenArrayOperatorEntry
                        && $token->getOpen() == $lastToken->getOpen());
                if ($emptyClosing) {
                    if (end($operationStack) instanceof HasName) {
                        $this->debugLog[] = 'Popping ' . end($operationStack)->getName() . ' from operationStack';
                    } else {
                        $this->debugLog[] = 'Popping ' . var_export(end($operationStack), true) . ' from operationStack';
                    }
                    array_pop($operationStack);
                    //Specifically handle immediately closed groups and arrays so the open operator doesn't consume a token
                    $this->postfixTokenArray[] = [];
                    $this->debugLog[] = 'Pushing [] onto postfixTokenArray';
                    if ($token instanceof CloseGroupOperatorEntry && end($operationStack) instanceof FunctionEntry) {
                        $result = $this->callOperation(array_pop($operationStack));
                        $this->postfixTokenArray[] = $result;
                        $this->debugLog[] = 'Pushing ' . var_export($result, true) . ' onto postfixTokenArray';
                    }
                } else
                    if (($expectNullaryOrPrefixUnary &&
                            !(!$token instanceof HasOperation
                                || $token instanceof HasNullaryOperation
                                || ($token instanceof HasUnaryOperation && $token->isPrefix())))
                        || ($expectBinaryOrPostfixUnary &&
                            !($token instanceof HasBinaryOperation
                                || ($token instanceof HasUnaryOperation && $token->isPostfix())))) {
                        if ($token instanceof HasOperation) {
                            $this->error = 'Unexpected operator: ' . $token->getName();
                        } else {
                            $this->error = 'Unexpected operation, received: ' . var_export($token, true);
                        }
//                    $this->error = 'Unexpected operator: ' . $token->getName()
//                        . (isset($lastToken) ? '<br/>Following token = '
//                            . ($lastToken instanceof HasName ? $lastToken->getName() : $lastToken) . '' : '')
//                        . '<br/>So: $expectNullaryOrPrefixUnary = ' . $expectNullaryOrPrefixUnary
//                        . ' and $expectBinaryOrPostfixUnary = ' . $expectBinaryOrPostfixUnary;
//                    $this->error = 'Unexpected operator: ' . var_export($token, true)
//                        . (isset($lastToken) ? '<br/>' . ' following token = ' . var_export($lastToken, true) : '')
//                        . '<br/>So: $expectNullaryOrPrefixUnary = ' . var_export($expectNullaryOrPrefixUnary, true)
//                        . ' and $expectBinaryOrPostfixUnary = ' . var_export($expectBinaryOrPostfixUnary, true);
                        $this->debugLog[] = $this->error;
                        return;
                    } elseif (is_string($token) && $this->operationRegistry->isSymbolName($token)) {
                        $this->error = 'No operation found for ' . $token . '<br/>';
                        $this->debugLog[] = $this->error;
                        return;
                    } elseif ($token instanceof HasOperation) {
                        $operation = $token;
                        $groupOpenFound = false;
                        $arrayOpenFound = false;
                        while ($this->success() && !empty($operationStack) && $this->shouldProcessOperator($operation, end($operationStack))) {
                            $popped = array_pop($operationStack);
                            $result = $this->callOperation($popped);
                            $this->postfixTokenArray[] = $result;
                            $this->debugLog[] = 'Pushing ' . var_export($result, true) . ' onto postfixTokenArray';
                            $groupOpenFound = $operation instanceof CloseGroupOperatorEntry && $operation->getOpen() == $popped->getName();
                            $arrayOpenFound = $operation instanceof CloseArrayOperatorEntry && $operation->getOpen() == $popped->getName();
                            if ($groupOpenFound) {
                                //specifically check for function calls before matched open parenthesis
                                if (end($operationStack) instanceof FunctionEntry) {
                                    $result = $this->callOperation(array_pop($operationStack));
                                    $this->postfixTokenArray[] = $result;
                                    $this->debugLog[] = 'Pushing ' . var_export($result, true) . ' onto postfixTokenArray';
                                }
                                break;
                            }
                            if ($arrayOpenFound) {
                                break;
                            }
                            if ($popped instanceof FunctionEntry) {
                                $this->error = 'No arguments passed to function: ' . $popped->getName() . ' (Function calls require parenthesis)';
                                $this->debugLog[] = $this->error;
                                return;
                            }
                        }
                        if ($operation instanceof CloseGroupOperatorEntry && !$groupOpenFound) {
                            $this->error = 'Mismatched group operator: ' . $operation->getClose();
                            $this->debugLog[] = $this->error;
                            return;
                        }
                        if ($operation instanceof CloseArrayOperatorEntry && !$arrayOpenFound) {
                            $this->error = 'Mismatched array operator: ' . $operation->getClose();
                            $this->debugLog[] = $this->error;
                            return;
                        }
                        if ($this->success() && !$operation instanceof CloseGroupOperatorEntry && !$operation instanceof CloseArrayOperatorEntry) {
                            $operationStack[] = $operation;
                            $this->debugLog[] = 'Pushing ' . $token->getName() . ' onto operationStack';
                        }
                    } elseif (!is_array($token) && strval(intval($token)) == strval($token)) {
                        $this->postfixTokenArray[] = intval($token);
                        $this->debugLog[] = 'Pushing ' . intval($token) . ' onto postfixTokenArray';
                    } elseif (!is_array($token) && strval(floatval($token)) == strval($token)) {
                        $this->postfixTokenArray[] = floatval($token);
                        $this->debugLog[] = 'Pushing ' . floatval($token) . ' onto postfixTokenArray';
                    } else {
                        $this->error = 'Unrecognized token: ' . var_export($token, true);
                    }
                $lastToken = $token;
            }
            if ($this->success()) {
                $this->debugLog[] = 'Reached end of postfixTokenArray, process all operators on the operationStack';
            }
            while ($this->success() && count($operationStack) > 1) {
                $operation = array_pop($operationStack);
                $operatorName = $operation->getName();
                $this->debugLog[] = 'Popping ' . $operatorName . ' from operationStack';
                if ($this->operationRegistry->isOpenGroupOperatorName($operatorName)) {
                    $this->error = 'Mismatched group operator: ' . $operatorName;
                    break;
                }
                if ($this->operationRegistry->isOpenArrayOperatorName($operatorName)) {
                    $this->error = 'Mismatched array operator: ' . $operatorName;
                    break;
                }
                if ($this->operationRegistry->isFunctionName($operatorName)) {
                    $this->error = 'No arguments passed to function: ' . $operatorName . ' (Function calls require parenthesis)';
                    break;
                }

                $result = $this->callOperation($operation);
                $this->postfixTokenArray[] = $result;
                $this->debugLog[] = 'Pushing ' . var_export($result, true) . ' onto postfixTokenArray';
            }
            if ($this->success()) {
                if (empty($this->postfixTokenArray)) {
                    $this->error = 'Invalid input';
                } elseif (count($this->postfixTokenArray) > 1) {
                    $this->error = 'Too few operations provided';
                } else {
                    $result = array_pop($this->postfixTokenArray);
                    if ($result instanceof HasGroup) {
                        $this->error = 'Unexpected operator: ,';
                        $this->debugLog[] = $this->error;
                        $this->debugLog[] = 'Final state: ' . var_export($result, true);
                    } else {
                        $this->processingResult = $result;
                        $this->debugLog[] = 'Found result: ' . var_export($result, true);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->debugLog[] = $this->error;
            $this->debugLog[] = var_export($this->postfixTokenArray, true);
        }
    }

    private function callOperation($operation)
    {
        $result = null;
        if ($operation instanceof NullaryOperatorEntry) {
            $result = $operation->call();
            $this->debugLog[] = 'Call to ' . $operation->getName() . ' yielded ' . var_export($result, true) . '';
        } elseif ($operation instanceof UnaryOperatorEntry) {
            if (count($this->postfixTokenArray) < 1) {
                $this->error = 'No operand provided for operator: ' . $operation->getName();
                return null;
            }
            $operand = array_pop($this->postfixTokenArray);
            $result = $operation->call($operand);
            $this->debugLog[] = 'Call to ' . $operation->getName() . ' with ' . var_export($operand, true) .
                ' yielded ' . var_export($result, true) . '';
        } elseif ($operation instanceof BinaryOperatorEntry) {
            if (count($this->postfixTokenArray) < 2) {
                $this->error = 'Too few operands provided for operator: ' . $operation->getName();
                return null;
            }
            $operand2 = array_pop($this->postfixTokenArray);
            $operand1 = array_pop($this->postfixTokenArray);
            $result = $operation->call($operand1, $operand2);
            $this->debugLog[] = 'Call to ' . $operation->getName() . ' with ' .
                var_export($operand1, true) . ' and ' . var_export($operand2, true) .
                ' yielded ' . var_export($result, true) . '';
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

    public function getLog(): array
    {
        return $this->log;
    }

    public function getDebugLog(): array
    {
        return $this->debugLog;
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