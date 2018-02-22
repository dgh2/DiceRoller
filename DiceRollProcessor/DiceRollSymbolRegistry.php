<?php

require_once('ShuntingYard/OperatorRegistry/BinaryOperatorEntry.php');
require_once('ShuntingYard/OperatorRegistry/CloseArrayOperatorEntry.php');
require_once('ShuntingYard/OperatorRegistry/CloseGroupOperatorEntry.php');
require_once('ShuntingYard/OperatorRegistry/FunctionEntry.php');
require_once('ShuntingYard/OperatorRegistry/NullaryOperatorEntry.php');
require_once('ShuntingYard/OperatorRegistry/OpenArrayOperatorEntry.php');
require_once('ShuntingYard/OperatorRegistry/OpenGroupOperatorEntry.php');
require_once('ShuntingYard/OperatorRegistry/SeparatorEntry.php');
require_once('ShuntingYard/OperatorRegistry/UnaryOperatorEntry.php');
require_once('ShuntingYard/SymbolRegistry.php');

use ShuntingYard\OperatorRegistry\BinaryOperatorEntry;
use ShuntingYard\OperatorRegistry\CloseArrayOperatorEntry;
use ShuntingYard\OperatorRegistry\CloseGroupOperatorEntry;
use ShuntingYard\OperatorRegistry\FunctionEntry;
use ShuntingYard\OperatorRegistry\NullaryOperatorEntry;
use ShuntingYard\OperatorRegistry\OpenArrayOperatorEntry;
use ShuntingYard\OperatorRegistry\OpenGroupOperatorEntry;
use ShuntingYard\OperatorRegistry\SeparatorEntry;
use ShuntingYard\OperatorRegistry\UnaryOperatorEntry;
use ShuntingYard\SymbolRegistry;

class DiceRollSymbolRegistry extends SymbolRegistry
{
    public const MAXIMUM_ALLOWED_DICE_COUNT = 500;

    protected $fullLog = [];
    protected $rollLog = [];
    protected $rawRolls = [];
    protected $calculationLog = [];

    public function logRoll($message)
    {
        $this->rollLog[] = $message;
        $this->fullLog[] = $message;
    }

    public function logCalculation($message)
    {
        $this->calculationLog[] = $message;
        $this->fullLog[] = $message;
    }

    public function clearLogs()
    {
        $this->rollLog = [];
        $this->calculationLog = [];
    }

    public function recursiveImplode($glue, $array)
    {
        if (is_numeric($array)) {
            return floatval($array);
        } elseif (is_array($array)) {
            $result = '';
            foreach ($array as $value) {
                if ($result !== '') {
                    $result .= $glue;
                }
                $result .= $this->recursiveImplode($glue, $value);
            }
            return '[' . $result . ']';
        } else {
            return var_export($array, true);
        }
    }

    protected function getSideArrayString($sideArray)
    {
        sort($sideArray);
        if ($sideArray == range(1, 100)) {
            $sideArrayString = '%';
        } elseif ($sideArray == range(-1, 1)) {
            $sideArrayString = 'f';
        } elseif (is_numeric(end($sideArray)) && $sideArray == range(1, end($sideArray))) {
            $sideArrayString = end($sideArray);
        } elseif (is_numeric(reset($sideArray)) && $sideArray == range(reset($sideArray), -1)) {
            $sideArrayString = '(' . reset($sideArray) . ')';
        } elseif (count($sideArray) == 1 && reset($sideArray) == 0) {
            $sideArrayString = '0';
        } elseif (count($sideArray) == 1) {
            $sideArrayString = '[' . $this->recursiveImplode(',', reset($sideArray)) . ']';
        } else {
            $sideArrayString = $this->recursiveImplode(',', $sideArray);
        }
        return $sideArrayString;
    }

    protected function add($operand1, $operand2)
    {
        $log = $this->recursiveImplode(',', $operand1) . '+' . $this->recursiveImplode(',', $operand2);
        if (!is_array($operand1) && !is_array($operand2)
            && (is_numeric($operand1) || is_infinite($operand1) || is_nan($operand1))
            && (is_numeric($operand2) || is_infinite($operand2) || is_nan($operand2))) {
            $result = $operand1 + $operand2;
        } elseif (is_array($operand1) && is_array($operand2)) {
            if (count($operand1) != count($operand2)) {
                $result = array_merge($operand1, $operand2);
            } else {
                $result = [];
                for ($i = 0; $i < count($operand1); $i++) {
                    $result[] = $this->add($operand1[$i], $operand2[$i]);
                }
            }
        } else {
            throw new \InvalidArgumentException('Unable to perform operation: ' . $log);
        }
        $log .= ' = ' . $this->recursiveImplode(',', $result);
        $this->logCalculation($log);
        return $result;
    }

    protected function subtract($operand1, $operand2)
    {
        $log = $this->recursiveImplode(',', $operand1) . '-' . $this->recursiveImplode(',', $operand2);
        if ((is_numeric($operand1) || is_infinite($operand1) || is_nan($operand1))
            && (is_numeric($operand2) || is_infinite($operand2) || is_nan($operand2))) {
            $result = $operand1 - $operand2;
        } elseif (is_array($operand1) && is_array($operand2) && count($operand1) == count($operand2)) {
            $result = [];
            for ($i = 0; $i < count($operand1); $i++) {
                $result[] = $this->subtract($operand1[$i], $operand2[$i]);
            }
        } else {
            throw new \InvalidArgumentException('Unable to perform operation: ' . $log);
        }
        $log .= ' = ' . $this->recursiveImplode(',', $result);
        $this->logCalculation($log);
        return $result;
    }

    protected function negate($operand)
    {
        $log = '-(' . $this->recursiveImplode(',', $operand) . ')';
        $result = $this->multiply(-1, $operand);
        $log .= ' = ' . $this->recursiveImplode(',', $result);
        $this->logCalculation($log);
        return $result;
    }

    protected function multiply($operand1, $operand2)
    {
        $log = $this->recursiveImplode(',', $operand1) . '*' . $this->recursiveImplode(',', $operand2);
        if (!is_array($operand1) && (is_numeric($operand1) || is_infinite($operand1) || is_nan($operand1))
            && !is_array($operand2) && (is_numeric($operand2) || is_infinite($operand2) || is_nan($operand2))) {
            $result = floatval($operand1) * floatval($operand2);
        } elseif (is_array($operand1) && !is_array($operand2) && (is_numeric($operand2) || is_infinite($operand2) || is_nan($operand2))) {
            $result = [];
            for ($i = 0; $i < count($operand1); $i++) {
                $result[] = $this->multiply($operand1[$i], $operand2);
            }
        } elseif (!is_array($operand1) && (is_numeric($operand1) || is_infinite($operand1) || is_nan($operand1)) && is_array($operand2)) {
            $result = [];
            for ($i = 0; $i < count($operand2); $i++) {
                $result[] = $this->multiply($operand1, $operand2[$i]);
            }
        } elseif (is_array($operand1) && is_array($operand2) && count($operand1) == count($operand2)) {
            $result = [];
            for ($i = 0; $i < count($operand1); $i++) {
                $result[] = $this->multiply($operand1[$i], $operand2[$i]);
            }
        } else {
            throw new \InvalidArgumentException('Unable to perform operation: ' . $log);
        }
        $log .= ' = ' . $this->recursiveImplode(',', $result);
        $this->logCalculation($log);
        return $result;
    }

    protected function divide($operand1, $operand2)
    {
        $log = $this->recursiveImplode(',', $operand1) . '/' . $this->recursiveImplode(',', $operand2);
        if (!is_array($operand1) && (is_numeric($operand1) || is_infinite($operand1) || is_nan($operand1))
            && !is_array($operand2) && (is_numeric($operand2) || is_infinite($operand2) || is_nan($operand2))) {
            $safeOperand2 = ($operand2 == 0 ? (floatval($operand2) * INF) : $operand2);
            $result = floatval($operand1) / floatval($safeOperand2);
        } elseif (is_array($operand1) && !is_array($operand2) && is_numeric($operand2)) {
            $result = [];
            for ($i = 0; $i < count($operand1); $i++) {
                $result[] = $this->divide($operand1[$i], $operand2);
            }
        } elseif (!is_array($operand1) && is_numeric($operand1) && is_array($operand2) && !empty($operand2)) {
            $result = [];
            for ($i = 0; $i < count($operand2); $i++) {
                $result[] = $this->divide($operand1, $operand2[$i]);
            }
        } elseif (is_array($operand1) && is_array($operand2) && count($operand1) == count($operand2) && !empty($operand2)) {
            $result = [];
            for ($i = 0; $i < count($operand1); $i++) {
                $result[] = $this->divide($operand1[$i], $operand2[$i]);
            }
        } else {
            throw new \InvalidArgumentException('Unable to perform operation: ' . $log);
        }
        $log .= ' = ' . $this->recursiveImplode(',', $result);
        $this->logCalculation($log);
        return $result;
    }

    protected function sum($array) {
        if (!is_array($array)) {
            throw new \InvalidArgumentException('Non-array value ' . $this->recursiveImplode(',', $array)
                . ' passed to function: sum');
        }
        $result = 0;
        foreach ($array as $item) {
            if (!is_numeric($item)) {
                throw new \InvalidArgumentException('Non-numeric value ' . $this->recursiveImplode(',', $item)
                    . ' in values passed to: sum');
            }
            $result += $item;
        }
        $this->logCalculation('sum(' . $this->recursiveImplode(',', $array) . ') = '
            . $this->recursiveImplode(',', $result));
        return $result;
    }

    protected function top($array) {
        if (!is_array($array) || count($array) < 2) {
            throw new \InvalidArgumentException('Too few arguments passed to function: top');
        } elseif (count($array) > 2) {
            throw new \InvalidArgumentException('Too many arguments passed to function: top');
        }
        $keepCount = reset($array);
        $items = end($array);
        if (!is_numeric($keepCount)) {
            throw new \InvalidArgumentException('Non-numeric value ' . $this->recursiveImplode(',', $keepCount)
                . ' passed as first argument to function: top');
        } elseif (!is_array($items)) {
            throw new \InvalidArgumentException('Non-array value ' . $this->recursiveImplode(',', $items)
                . ' passed as second argument to function: top');
        } elseif ($keepCount > count($items)) {
            throw new \InvalidArgumentException($keepCount . ' numbers were requested but ' . count($items)
                . ' were passed to function: top');
        }
        $log = 'top(' . $keepCount . ', ' . $this->recursiveImplode(',', $items) . ')';

        for ($i = count($items) - $keepCount; $i > 0; $i--) {
            array_splice($items, array_search(min($items), $items), 1);
        }
        $log .= ' = ' . $this->recursiveImplode(',', $items);
        $this->logCalculation($log);
        return $items;
    }

    protected function bottom($array) {
        if (!is_array($array) || count($array) < 2) {
            throw new \InvalidArgumentException('Too few arguments passed to function: bottom');
        } elseif (count($array) > 2) {
            throw new \InvalidArgumentException('Too many arguments passed to function: bottom');
        }
        $keepCount = reset($array);
        $items = end($array);
        if (!is_numeric($keepCount)) {
            throw new \InvalidArgumentException('Non-numeric value ' . $this->recursiveImplode(',', $keepCount)
                . ' passed as first argument to function: bottom');
        } elseif (!is_array($items)) {
            throw new \InvalidArgumentException('Non-array value ' . $this->recursiveImplode(',', $items)
                . ' passed as second argument to function: bottom');
        } elseif ($keepCount > count($items)) {
            throw new \InvalidArgumentException($keepCount . ' numbers were requested but ' . count($items)
                . ' were passed to function: bottom');
        }
        $log = 'bottom(' . $keepCount . ', ' . $this->recursiveImplode(',', $items) . ')';

        for ($i = count($items) - $keepCount; $i > 0; $i--) {
            array_splice($items, array_search(max($items), $items), 1);
        }
        $log .= ' = ' . $this->recursiveImplode(',', $items);
        $this->logCalculation($log);
        return $items;
    }

    protected function rawDiceRoll($diceCount, $sideArray)
    {
        if (is_array($sideArray)) {
            return self::diceRoll($diceCount, $sideArray);
        } elseif ($sideArray < 0) {
            return self::diceRoll($diceCount, range($sideArray, -1));
        } elseif ($sideArray == 0) {
            return self::diceRoll($diceCount, [0]);
        } else {
            return self::diceRoll($diceCount, range(1, $sideArray));
        }
    }

    protected function diceRoll($diceCount, $sideArray)
    {
        if (!is_numeric($diceCount) || intval($diceCount) != round($diceCount)) {
            throw new \InvalidArgumentException('The number of dice to roll must be an integer. '
                . 'Received: ' . $this->recursiveImplode(',', $diceCount));
        } elseif (intval($diceCount) <= 0) {
            throw new \InvalidArgumentException('The number of dice to roll must be greater than 0. '
                . 'Received: ' . intval($diceCount));
        } elseif (intval($diceCount) > self::MAXIMUM_ALLOWED_DICE_COUNT) {
            throw new \InvalidArgumentException('A maximum of ' . self::MAXIMUM_ALLOWED_DICE_COUNT
                . ' dice may be rolled at a time (' . intval($diceCount) . ' > ' . self::MAXIMUM_ALLOWED_DICE_COUNT . ')');
        } elseif (!is_array($sideArray)) {
            throw new \InvalidArgumentException('The sides of dice must be passed as an array. '
                . 'Received: ' . $this->recursiveImplode(',', $sideArray));
        } elseif (empty($sideArray)) {
            throw new \InvalidArgumentException('Dice must have at least one side.');
        }

        $result = null;
        $rollLog = '';
        try {
            if (count($sideArray) == 1) {
                $result = $this->multiply($diceCount, $sideArray[0]);
                $this->rawRolls = array_merge($this->rawRolls, array_fill(0, $diceCount, $sideArray[0]));
                return $result;
            }

            $rolls = [];
            if ($diceCount == 1) {
                $result = $sideArray[rand(1, count($sideArray)) - 1];
                $this->rawRolls[] = $result;
                $rolls[] = $result;
                return $result;
            }

            for ($i = 0; $i < intval($diceCount); $i++) {
                $roll = $sideArray[rand(1, count($sideArray)) - 1];
                $this->rawRolls[] = $roll;
                $rolls[] = $roll;
                if (isset($result)) {
                    $result = $this->add($result, $roll);
                } else {
                    $result = $roll;
                }
            }

            if (!empty($rolls)) {
                $rollLog .= ' with rolls: ' . substr($this->recursiveImplode(', ', $rolls), 1, -1);
            }
            return $result;
        } finally {
            $this->logRoll($this->recursiveImplode(',', $diceCount) . 'd' . self::getSideArrayString($sideArray) . ' = '
                . $this->recursiveImplode(',', $result) . $rollLog);
        }
    }

    protected function percentileDiceRoll($diceCount)
    {
        return self::diceRoll($diceCount, range(1, 100));
    }

    protected function fudgeDiceRoll($diceCount)
    {
        return self::diceRoll($diceCount, range(-1, 1));
    }

    public function __construct()
    {
        $openParenthesis = new OpenGroupOperatorEntry('(');
        $this->register($openParenthesis);
        $this->register(new CloseGroupOperatorEntry(')', $openParenthesis));
        $openSquareBracket = new OpenArrayOperatorEntry('[');
        $this->register($openSquareBracket);
        $this->register(new CloseArrayOperatorEntry(']', $openSquareBracket));
        $this->register(new SeparatorEntry(','));

        $this->register(new NullaryOperatorEntry('PI', function () {
            $this->logCalculation('PI = ' . M_PI);
            return M_PI;
        }));
        $this->register(new BinaryOperatorEntry('+', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 2, function ($operand1, $operand2) {
            return $this->add($operand1, $operand2);
        }));
        $this->register(new BinaryOperatorEntry('-', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 2, function ($operand1, $operand2) {
            return $this->subtract($operand1, $operand2);
        }));
        $this->register(new BinaryOperatorEntry('*', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 3, function ($operand1, $operand2) {
            return $this->multiply($operand1, $operand2);
        }));
        $this->register(new BinaryOperatorEntry('/', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 3, function ($operand1, $operand2) {
            return $this->divide($operand1, $operand2);
        }));
        $this->register(new UnaryOperatorEntry('+', UnaryOperatorEntry::PREFIX, function ($operand) {
            return $operand;
        }));
        $this->register(new UnaryOperatorEntry('-', UnaryOperatorEntry::PREFIX, function ($operand) {
            return $this->negate($operand);
        }));
        $this->register(new UnaryOperatorEntry('!', UnaryOperatorEntry::POSTFIX, function ($operand) {
            $log = $this->recursiveImplode(',', $operand) . '!';
            $result = NAN;
            if (!is_array($operand) && is_numeric($operand) && $operand > 0) {
                if ($operand > 170) { //numbers greater than 170 return INF for !
                    $result = INF;
                } elseif (strval(intval($operand)) == strval($operand)) {
                    $result = 1;
                    for ($i = intval($operand); $i > 1 && !is_infinite($result); $i--) {
                        $result *= $i;
                    }
                } else {
                    throw new \InvalidArgumentException('Unable to perform operation: ' . $log);
                }
            }
            $this->logCalculation($this->recursiveImplode(',', $operand) . '! = ' . $this->recursiveImplode(',', $result));
            return $result;
        }));
        $this->register(new UnaryOperatorEntry('d%', UnaryOperatorEntry::POSTFIX, function ($operand) {
            return self::percentileDiceRoll($operand);
        }));
        $this->register(new UnaryOperatorEntry('df', UnaryOperatorEntry::POSTFIX, function ($operand) {
            return self::fudgeDiceRoll($operand);
        }));
        $this->register(new UnaryOperatorEntry('dF', UnaryOperatorEntry::POSTFIX, function ($operand) {
            return self::fudgeDiceRoll($operand);
        }));
        $this->register(new NullaryOperatorEntry('d%', function () {
            return self::percentileDiceRoll(1);
        }));
        $this->register(new NullaryOperatorEntry('df', function () {
            return self::fudgeDiceRoll(1);
        }));
        $this->register(new NullaryOperatorEntry('dF', function () {
            return self::fudgeDiceRoll(1);
        }));
        $this->register(new UnaryOperatorEntry('$r', UnaryOperatorEntry::PREFIX, function ($index) {
            $log = '$r' . $this->recursiveImplode(',', $index);
            if (!is_numeric($index) || strval(intval($index)) != strval($index)) {
                throw new \InvalidArgumentException('Unable to perform operation: ' . $log);
            } elseif (empty($this->rawRolls)) {
                throw new \InvalidArgumentException('Unable to perform operation: ' . $log
                    . ' (No rolls logged yet)');
            } elseif ($index >= count($this->rawRolls) || $index < -count($this->rawRolls)) {
                $rangeString = -count($this->rawRolls) . ', ' . (count($this->rawRolls) - 1);
                throw new \InvalidArgumentException('Unable to perform operation: ' . $log
                    . ' (Allowed range: [' . $rangeString . '])');
            }
            $result = null;
            if ($index < 0) {
                $result = $this->rawRolls[$index + count($this->rawRolls)];
            } else {
                $result = $this->rawRolls[$index];
            }
            $this->logCalculation($log . ' = ' . $this->recursiveImplode(',', $result));
            return $result;
        }));
        $this->register(new BinaryOperatorEntry('d', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 3, function ($operand1, $operand2) {
            return self::rawDiceRoll($operand1, $operand2);
        }));
        $this->register(new UnaryOperatorEntry('d', UnaryOperatorEntry::PREFIX, function ($operand) {
            return self::rawDiceRoll(1, $operand);
        }));

        $this->register(new FunctionEntry('max', function ($array) {
            if (empty($array)) {
                throw new \InvalidArgumentException('Too few arguments passed to function: max');
            }
            $result = is_array($array) ? max($array) : $array;
            $this->logCalculation('max(' . $this->recursiveImplode(',', $array) . ') = ' . $this->recursiveImplode(',', $result));
            return $result;
        }));
        $this->register(new FunctionEntry('min', function ($array) {
            if (empty($array)) {
                throw new \InvalidArgumentException('Too few arguments passed to function: min');
            }
            $result = is_array($array) ? min($array) : $array;
            $this->logCalculation('min(' . $this->recursiveImplode(',', $array) . ') = ' . $this->recursiveImplode(',', $result));
            return $result;
        }));

        $this->register(new FunctionEntry('top', function ($array) {
            return self::top($array);
        }));
        $this->register(new FunctionEntry('bottom', function ($array) {
            return self::bottom($array);
        }));

        $this->register(new FunctionEntry('sum', function ($array) {
            return self::sum($array);
        }));

        $this->register(new FunctionEntry('stats', function ($array) {
            if (!is_array($array) && is_numeric($array)) {
                $statCount = $array;
                $keepCount = $array;
            } elseif (!is_array($array)) {
                throw new \InvalidArgumentException('Non-array value ' . $this->recursiveImplode(',', $array)
                    . ' passed to function: stats');
            } elseif (count($array) < 1) {
                throw new \InvalidArgumentException('Too few values passed to function: stats');
            } elseif (count($array) > 2) {
                throw new \InvalidArgumentException('Too many values passed to function: stats');
            } else {
                $statCount = reset($array);
                if (count($array) == 1) {
                    $keepCount = count($array);
                } else {
                    $keepCount = end($array);
                }
            }
            if (!is_numeric($statCount)) {
                throw new \InvalidArgumentException('Non-numeric value ' . $this->recursiveImplode(',', $statCount)
                    . ' passed to function: stats');
            } elseif (!is_numeric($keepCount)) {
                throw new \InvalidArgumentException('Non-numeric value ' . $this->recursiveImplode(',', $keepCount)
                    . ' passed to function: stats');
            }
            $result = [];
            for ($i = 0; $i < $statCount; $i++) {
                $result[] = self::sum(self::top([3, [self::rawDiceRoll(1,6), self::rawDiceRoll(1,6), self::rawDiceRoll(1,6), self::rawDiceRoll(1,6)]]));
            }
            $result = self::top([$keepCount, $result]);
            $this->logCalculation('stats(' . $this->recursiveImplode(',', $statCount) . ') = '
                . $this->recursiveImplode(',', $result));
            return $result;
        }));
    }

    public function getRollLog(): array
    {
        return $this->rollLog;
    }

    public function getCalculationLog(): array
    {
        return $this->calculationLog;
    }

    public function getLogString($separator, $showCalculationLog = false, $showRollLog = true): String
    {
        if ($showCalculationLog && $showRollLog) {
            $sourceLog = $this->fullLog;
        } elseif ($showCalculationLog) {
            $sourceLog = $this->calculationLog;
        } elseif ($showRollLog) {
            $sourceLog = $this->rollLog;
        } else {
            $sourceLog = [];
        }

        $log = '';
        foreach ($sourceLog as $message) {
            if ($log != '') {
                $log .= $separator;
            }
            $log .= $message;
        }
        return $log;
    }
}