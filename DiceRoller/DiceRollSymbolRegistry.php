<?php

namespace DiceRoller;

use DiceRoller\OperatorRegistry\BinaryOperatorEntry;
use DiceRoller\OperatorRegistry\NullaryOperatorEntry;
use DiceRoller\OperatorRegistry\UnaryOperatorEntry;
use InvalidArgumentException;

class DiceRollSymbolRegistry extends SymbolRegistry
{
    public const MAXIMUM_ALLOWED_DICE_COUNT = 500;

    private $log = [];

    public function log($message)
    {
        $this->log[] = $message;
    }

    public function clearLog()
    {
        $this->log = [];
    }

    public function __construct()
    {
        $this->register(new NullaryOperatorEntry('PI', function () {
            $this->log('PI = ' . var_export(M_PI, true));
            return M_PI;
        }));
        $this->register(new NullaryOperatorEntry('F', function () {
            $this->log('F = [-1,0,1]');
            return array(-1, 0, 1);
        }));
        $this->register(new NullaryOperatorEntry('f', function () {
            $this->log('f = [-1,0,1]');
            return array(-1, 0, 1);
        }));
        $this->register(new NullaryOperatorEntry('%', function () {
            $this->log('% = range(1,100)');
            return range(1, 100);
        }));
        $this->register(new BinaryOperatorEntry('+', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 2, function ($operand1, $operand2) {
            $log = var_export($operand1, true) . " + " . var_export($operand2, true) . " = ";
            if (is_array($operand1) && !is_array($operand2)) {
                foreach ($operand1 as &$value) {
                    $value = $value + $operand2;
                }
                unset($value);
                $result = $operand1;
            } elseif (!is_array($operand1) && is_array($operand2)) {
                foreach ($operand2 as &$value) {
                    $value = $value + $operand1;
                }
                unset($value);
                $result = $operand2;
            } elseif (is_array($operand1) && is_array($operand2) && count($operand1) == 2 && count($operand2) == 2) {
                $result = array();
                for ($i = 0; $i < count($operand1); $i++) {
                    $result[] = $operand1[$i] + $operand2[$i];
                }
            } elseif (is_array($operand1) && is_array($operand2)) {
                $result = array_merge($operand1, $operand2);
            } else {
                $result = $operand1 + $operand2;
            }
            $log .= var_export($result, true);
            $this->log($log);
            return $result;
        }));
        $this->register(new BinaryOperatorEntry('-', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 2, function ($operand1, $operand2) {
            $log = var_export($operand1, true) . " - " . var_export($operand2, true) . " = ";
            if (is_array($operand1) && !is_array($operand2)) {
                foreach ($operand1 as &$value) {
                    $value = $value - $operand2;
                }
                unset($value);
                $result = $operand1;
            } elseif (!is_array($operand1) && is_array($operand2)) {
                foreach ($operand2 as &$value) {
                    $value = $value - $operand1;
                }
                unset($value);
                $result = $operand2;
            } elseif (is_array($operand1) && is_array($operand2) && count($operand1) == 2 && count($operand2) == 2) {
                $result = array();
                for ($i = 0; $i < count($operand1); $i++) {
                    $result[] = $operand1[$i] - $operand2[$i];
                }
            } elseif (is_array($operand1) && is_array($operand2)) {
                $result = array_merge($operand1, $operand2);
            } else {
                $result = $operand1 - $operand2;
            }
            $log .= var_export($result, true);
            $this->log($log);
            return $result;
        }));
        $this->register(new BinaryOperatorEntry('d', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 3, function ($operand1, $operand2) {
            $result = NAN;
            $rolls = array();
            if (is_int($operand1) || strval(intval($operand1)) == strval($operand1)) {
                if (intval($operand1) > self::MAXIMUM_ALLOWED_DICE_COUNT) {
                    throw new InvalidArgumentException("A maximum of " . self::MAXIMUM_ALLOWED_DICE_COUNT .
                        " dice may be rolled at a time (" . intval($operand1) . " > " . self::MAXIMUM_ALLOWED_DICE_COUNT . ")");
                }
                for ($i = 0; $i < $operand1; $i++) {
                    if (is_infinite($result)) {
                        break;
                    }
                    if (is_nan($result)) {
                        $result = 0;
                    }
                    if (is_array($operand2)) {
                        $roll = rand(0, count($operand2) - 1);
                        $rollResult = current(array_slice($operand2, $roll, 1));
                        $rolls[] = $rollResult;
                        $result += $rollResult;
                        unset($rollResult);
                    } else {
                        $roll = rand(1, $operand2);
                        $rolls[] = $roll;
                        $result += $roll;
                    }
                }
            }
            $rollLog = '';
            if (!empty($rolls)) {
                $rollLog .= ' with rolls: ' . implode(', ', $rolls);
            }
            if (is_array($operand2)) {
                $before = implode(',', $operand2);
            } else {
                $before = var_export($operand2, true);
            }
            $this->log(var_export($operand1, true) . 'd' . $before . ' = ' . var_export($result, true) . $rollLog);
            return $result;
        }));
        $this->register(new BinaryOperatorEntry('*', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 3, function ($operand1, $operand2) {
            $result = $operand1 * $operand2;
            $this->log(var_export($operand1, true) . ' * ' . var_export($operand2, true) . ' = ' . $result);
            return $result;
        }));
        $this->register(new BinaryOperatorEntry('/', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 3, function ($operand1, $operand2) {
            $result = $operand2 == 0 ? NAN : $operand1 / $operand2;
            $this->log(var_export($operand1, true) . ' / ' . var_export($operand2, true) . ' = ' . $result);
            return $result;
        }));
        $this->register(new UnaryOperatorEntry('+', UnaryOperatorEntry::PREFIX, function ($operand) {
            return $operand;
        }));
        $this->register(new UnaryOperatorEntry('-', UnaryOperatorEntry::PREFIX, function ($operand) {
            if (is_array($operand)) {
                $before = implode(',', $operand);
                foreach ($operand as &$value) {
                    $value = -$value;
                }
                unset($value);
                $result = $operand;
                $after = implode(',', $operand);
            } else {
                $before = strval($operand);
                $result = -$operand;
                $after = strval($result);
            }
            $this->log("- $before = $after");
            return $result;
        }));
        $this->register(new UnaryOperatorEntry('d', UnaryOperatorEntry::PREFIX, function ($operand) {
            $result = NAN;
            if (is_array($operand)) {
                $roll = rand(0, count($operand) - 1);
                $result = current(array_slice($operand, $roll, 1));
            } elseif ($operand > 0) {
                $result = rand(1, $operand);
            }
            $this->log('d' . var_export($operand, true) . ' = ' . var_export($result, true));
            return $result;
        }));
        $this->register(new UnaryOperatorEntry('!', UnaryOperatorEntry::POSTFIX, function ($operand) {
            $result = NAN;
            if (!is_array($operand) && (is_int($operand) || strval(intval($operand)) == strval($operand)) && intval($operand) > 0) {
                $result = 1;
                if (intval($operand) >= 171) { //171 is the smallest number that naturally returns INF for ! (tested in PHP 7.1)
                    $result = INF;
                }
                for ($i = intval($operand); $i > 1; $i--) {
                    if (is_infinite($result)) {
                        break;
                    }
                    $result *= $i;
                }
            }
            $this->log(var_export($operand, true) . '! = ' . var_export($result, true));
            return $result;
        }));

        $openParenthesis = new OperatorRegistry\OpenGroupOperatorEntry('(');
        $this->register($openParenthesis);
        $this->register(new OperatorRegistry\CloseGroupOperatorEntry(')', $openParenthesis));
        $openSquareBracket = new OperatorRegistry\OpenArrayOperatorEntry('[');
        $this->register($openSquareBracket);
        $this->register(new OperatorRegistry\CloseArrayOperatorEntry(']', $openSquareBracket));
        $this->register(new OperatorRegistry\SeparatorEntry(','));
        $this->register(new OperatorRegistry\FunctionEntry("max", function ($array) {
            if (!is_array($array)) {
                $result = $array;
            } elseif (count($array) == 1) {
                $result = $array[0];
            } else {
                $result = max($array[0], ...array_slice($array, 1));
            }
            $this->log('max(' . var_export($array, true) . ') = ' . var_export($result, true));
            return $result;
        }));
        $this->register(new OperatorRegistry\FunctionEntry("min", function ($array) {
            if (!is_array($array)) {
                $this->log('min(' . var_export($array, true) . ') = ' . var_export($array, true));
                return $array;
            }
            if (count($array) == 1) {
                $this->log('min(' . var_export($array, true) . ') = ' . var_export($array[0], true));
                return $array[0];
            }
            $result = min($array[0], ...array_slice($array, 1));
            $this->log('min(' . var_export($array, true) . ') = ' . var_export($result, true));
            return $result;
        }));
    }

    public function getLog(): array
    {
        return $this->log;
    }

    public function getLogString($separator): String
    {
        $log = "";
        foreach ($this->log as $message) {
            if ($log != "") {
                $log .= $separator;
            }
            $log .= $message;
        }
        return $log;
    }
}