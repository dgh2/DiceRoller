<html>
<body><?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

spl_autoload_register(function ($class) {
    $file = str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once($file);
    }
});

const NBSP_PATTERN = '\xA0';
const DEFAULT_ROLL_COMMAND = 'd20';

$rollLog = array();

$inputHandler = new DiceRoller\InputHandler();
$wasPostReceived = $inputHandler->wasPostReceived();

$command = null;
$processingResult = null;
if ($wasPostReceived) { //post from interaction
    //clean the command
    $command = preg_replace('# |' . NBSP_PATTERN . '#', '', htmlentities($inputHandler->command(), ENT_QUOTES));

    //register an error handler to post back any error messages from exceptions
    register_shutdown_function(function () use ($inputHandler, $command) {
        $last_error = error_get_last();
        if ($last_error != null && $last_error['type'] === E_ERROR) {
            $postResponse = DiceRoller\HipChatHelper::generatePost($inputHandler->initiatorPrefix() .
                "An error occurred while processing $command: " . $last_error["message"]);
            DiceRoller\HipChatHelper::sendPost_Curl($postResponse, $inputHandler->roomId());
        }
    });
} elseif (isSet($_GET['roll'])) { //web interface
    $command = strip_tags($_GET['roll']);
    $command = htmlentities($command, ENT_QUOTES);
    $command = preg_replace('# |' . NBSP_PATTERN . '#', '', $command);
    echo DiceRoller\RollForm::generate($command);

    //register an error handler to echo out any error messages from exceptions
    register_shutdown_function(function () use ($command) {
        $last_error = error_get_last();
        if ($last_error['type'] === E_ERROR) {
            echo "<strong>An error occurred while processing $command: " . $last_error["message"] . "</strong>";
        }
    });
} else {
    echo DiceRoller\RollForm::generate(DEFAULT_ROLL_COMMAND);
}

if (isSet($command)) {
//    $symbolRegistry = new DiceRoller\SymbolRegistry();
//
//    $symbolRegistry->register(new NullaryOperatorEntry('PI', function () {
//        echo 'PI = ' . var_export(M_PI, true) . '<br/>';
//        return M_PI;
//    }));
//    $fudgeDiceOperation = function () {
//        echo 'F = [-1,0,1]<br/>';
//        return array(-1,0,1);
//    };
//    $symbolRegistry->register(new NullaryOperatorEntry('F', $fudgeDiceOperation));
//    $symbolRegistry->register(new NullaryOperatorEntry('f', $fudgeDiceOperation));
//    $symbolRegistry->register(new NullaryOperatorEntry('%', function () {
//        echo '% = range(1,100)<br/>';
//        return range(1,100);
//    }));
//    $symbolRegistry->register(new BinaryOperatorEntry('+', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 2, function ($operand1, $operand2) {
//        $log = var_export($operand1, true) . " + " . var_export($operand2, true) . " = ";
//        if (is_array($operand1) && !is_array($operand2)) {
//            foreach ($operand1 as &$value) {
//                $value = $value + $operand2;
//            }
//            unset($value);
//            $result = $operand1;
//        } elseif (!is_array($operand1) && is_array($operand2)) {
//            foreach ($operand2 as &$value) {
//                $value = $value + $operand1;
//            }
//            unset($value);
//            $result = $operand2;
//        } elseif (is_array($operand1) && is_array($operand2) && count($operand1) == 2 && count($operand2) == 2) {
//            $result = array();
//            for ($i = 0; $i < count($operand1); $i++) {
//                $result[] = $operand1[$i] + $operand2[$i];
//            }
//        } elseif (is_array($operand1) && is_array($operand2)) {
//            $result = array_merge($operand1, $operand2);
//        } else {
//            $result = $operand1 + $operand2;
//        }
//        $log .= var_export($result, true);
//        echo $log . '<br/>';
//        return $result;
//    }));
//    $symbolRegistry->register(new BinaryOperatorEntry('-', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 2, function ($operand1, $operand2) {
//        $log = var_export($operand1, true) . " - " . var_export($operand2, true) . " = ";
//        if (is_array($operand1) && !is_array($operand2)) {
//            foreach ($operand1 as &$value) {
//                $value = $value - $operand2;
//            }
//            unset($value);
//            $result = $operand1;
//        } elseif (!is_array($operand1) && is_array($operand2)) {
//            foreach ($operand2 as &$value) {
//                $value = $value - $operand1;
//            }
//            unset($value);
//            $result = $operand2;
//        } elseif (is_array($operand1) && is_array($operand2) && count($operand1) == 2 && count($operand2) == 2) {
//            $result = array();
//            for ($i = 0; $i < count($operand1); $i++) {
//                $result[] = $operand1[$i] - $operand2[$i];
//            }
//        } elseif (is_array($operand1) && is_array($operand2)) {
//            $result = array_merge($operand1, $operand2);
//        } else {
//            $result = $operand1 - $operand2;
//        }
//        $log .= var_export($result, true);
//        echo $log . '<br/>';
//        return $result;
//    }));
//    $symbolRegistry->register(new BinaryOperatorEntry('d', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 3, function ($operand1, $operand2) {
//        global $rollLog;
//
//        $result = NAN;
//        $rolls = array();
//        //if ($operand2 > 0
//        //    && (is_int($operand1) || strval(intval($operand1)) == strval($operand1))
//        //    && (is_int($operand2) || strval(intval($operand2)) == strval($operand2))) {
//        if (is_int($operand1) || strval(intval($operand1)) == strval($operand1)) {
//            if (intval($operand1) > 500) {
//                throw new InvalidArgumentException("A maximum of 500 dice may be rolled at a time (" . intval($operand1) . " > 500)");
//            }
//            for ($i = 0; $i < $operand1; $i++) {
//                if (is_infinite($result)) {
//                    break;
//                }
//                if (is_nan($result)) {
//                    $result = 0;
//                }
//                $rollResult = 0;
//                if (is_array($operand2)) {
//                    $roll = rand(0, count($operand2) - 1);
//                    $rollResult = current(array_slice($operand2, $roll, 1));
//                } else {
//                    $rollResult = rand(1, $operand2);
//                }
//                $rolls[] = $rollResult;
//                $result += $rollResult;
//                $rollLog[] = $rollResult;
//            }
//        }
//        $localRollLog = '';
//        if (!empty($rolls)) {
//            $localRollLog .= ' with rolls: ' . implode(', ', $rolls);
//        }
//        if (is_array($operand2)) {
//            $before = implode(',', $operand2);
//        } else {
//            $before = var_export($operand2, true);
//        }
//        echo var_export($operand1, true) . 'd' . $before . ' = ' . var_export($result, true) . $localRollLog . '<br/>';
//        return $result;
//    }));
//    $symbolRegistry->register(new BinaryOperatorEntry('*', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 3, function ($operand1, $operand2) {
//        $result = $operand1 * $operand2;
//        echo var_export($operand1, true) . ' * ' . var_export($operand2, true) . ' = ' . $result . '<br/>';
//        return $result;
//    }));
//    $symbolRegistry->register(new BinaryOperatorEntry('/', BinaryOperatorEntry::LEFT_ASSOCIATIVE, 3, function ($operand1, $operand2) {
//        $result = $operand2 == 0 ? NAN : $operand1 / $operand2;
//        echo var_export($operand1, true) . ' / ' . var_export($operand2, true) . ' = ' . $result . '<br/>';
//        return $result;
//    }));
//    $symbolRegistry->register(new UnaryOperatorEntry('+', UnaryOperatorEntry::PREFIX, function ($operand) {
//        return $operand;
//    }));
//    $symbolRegistry->register(new UnaryOperatorEntry('-', UnaryOperatorEntry::PREFIX, function ($operand) {
//        if (is_array($operand)) {
//            $before = implode(',', $operand);
//            foreach ($operand as &$value) {
//                $value = -$value;
//            }
//            unset($value);
//            $result = $operand;
//            $after = implode(',', $operand);
//        } else {
//            $before = strval($operand);
//            $result = -$operand;
//            $after = strval($result);
//        }
//        echo "- $before = $after<br/>";
//        return $result;
//    }));
//    $symbolRegistry->register(new UnaryOperatorEntry('d', UnaryOperatorEntry::PREFIX, function ($operand) {
//        global $rollLog;
//
//        $result = NAN;
//        if (is_array($operand)) {
//            $roll = rand(0, count($operand) - 1);
//            $result = current(array_slice($operand, $roll, 1));
//        } elseif ($operand > 0) {
//            $result = rand(1, $operand);
//        }
//        $rollLog[] = $result;
//        echo 'd' . var_export($operand, true) . ' = ' . var_export($result, true) . '<br/>';
//        return $result;
//    }));
//    $symbolRegistry->register(new UnaryOperatorEntry('!', UnaryOperatorEntry::POSTFIX, function ($operand) {
//        $result = NAN;
//        if (!is_array($operand) && (is_int($operand) || strval(intval($operand)) == strval($operand)) && intval($operand) > 0) {
//            $result = 1;
//            if (intval($operand) >= 171) { //171 is the smallest number that naturally returns INF for ! (tested in PHP 7.1)
//                $result = INF;
//            }
//            for ($i = intval($operand); $i > 1; $i--) {
//                if (is_infinite($result)) {
//                    break;
//                }
//                $result *= $i;
//            }
//        }
//        echo var_export($operand, true) . '! = ' . var_export($result, true) . '<br/>';
//        return $result;
//    }));
//
//    $openParenthesis = new \DiceRoller\OperatorRegistry\OpenGroupOperatorEntry('(');
//    $symbolRegistry->register($openParenthesis);
//    $symbolRegistry->register(new \DiceRoller\OperatorRegistry\CloseGroupOperatorEntry(')', $openParenthesis));
//    $openSquareBracket = new \DiceRoller\OperatorRegistry\OpenArrayOperatorEntry('[');
//    $symbolRegistry->register($openSquareBracket);
//    $symbolRegistry->register(new \DiceRoller\OperatorRegistry\CloseArrayOperatorEntry(']', $openSquareBracket));
//    $symbolRegistry->register(new \DiceRoller\OperatorRegistry\SeparatorEntry(','));
//    $symbolRegistry->register(new \DiceRoller\OperatorRegistry\FunctionEntry("max", function ($array) {
//        if (!is_array($array)) {
//            $result = $array;
//        } elseif (count($array) == 1) {
//            $result = $array[0];
//        } else {
//            $result = max($array[0], ...array_slice($array, 1));
//        }
//        echo 'max(' . var_export($array, true) . ') = ' . var_export($result, true) . '<br/>';
//        return $result;
//    }));
//    $symbolRegistry->register(new \DiceRoller\OperatorRegistry\FunctionEntry("min", function ($array) {
//        if (!is_array($array)) {
//            echo 'min(' . var_export($array, true) . ') = ' . var_export($array, true) . '<br/>';
//            return $array;
//        }
//        if (count($array) == 1) {
//            echo 'min(' . var_export($array, true) . ') = ' . var_export($array[0], true) . '<br/>';
//            return $array[0];
//        }
//        $result = min($array[0], ...array_slice($array, 1));
//        echo 'min(' . var_export($array, true) . ') = ' . var_export($result, true) . '<br/>';
//        return $result;
//    }));

    $symbolRegistry = new DiceRoller\DiceRollSymbolRegistry();
    $processor = new DiceRoller\ShuntingYardProcessor($symbolRegistry);

    try {
        $processor->processRawCommand($command);
    } finally {
        $processingResult = "";
        if ($processor->success()) {
            if ($command == \DiceRoller\InputHandler::HELP_COMMAND) {
                $processingResult = $processor->getProcessingResult();
            } else {
                $processingResult = "Result = " . var_export($processor->getProcessingResult(), true);
            }
        } else {
            $processingResult = "Failed to process input '$command' with error: " . $processor->getError();
        }

        if ($wasPostReceived) { //post was received, post result back with logs
            $responseMessage = $inputHandler->initiatorPrefix() . $processingResult;
            if (!empty($symbolRegistry->getLogString('<br/>'))) {
                $responseMessage .= '<br/>' . $symbolRegistry->getLogString('<br/>');
            }
            if (strlen($responseMessage) > \DiceRoller\HipChatHelper::MAX_MESSAGE_LENGTH) { //if response too long
                $responseMessage = $inputHandler->initiatorPrefix() . $processingResult; //just send result without logs
            }
            $postResponse = DiceRoller\HipChatHelper::generatePost($responseMessage);
            DiceRoller\HipChatHelper::sendPost_Curl($postResponse, $inputHandler->roomId());
        } else { //output result, log, and debug logs to the screen
            echo "<strong>$processingResult</strong>";
            if (!empty($symbolRegistry->getLogString('<br/>'))) {
                echo '<br/><br/>' . $symbolRegistry->getLogString('<br/>');
            }
            if (!empty($processor->getDevlog())) {
                echo '<br/><br/><br/>Debug logs:';
                foreach ($processor->getDevlog() as $processorLog) {
                    echo "<br/>$processorLog";
                }
            }
        }
    }
}
?></body>
</html>