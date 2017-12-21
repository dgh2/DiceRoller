<html>
<body><?php

use DiceRoller\DiceRollSymbolRegistry;

error_reporting(E_ALL);
ini_set("display_errors", 1);

spl_autoload_register(function ($class) {
    $file = str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once($file);
    }
});

$processingResult = "";
$rollLog = array();

$symbolRegistry = new DiceRollSymbolRegistry();
$processor = new DiceRoller\ShuntingYardProcessor($symbolRegistry);

$testCommand = function ($command, $expectedResult = null, $expectedError = null) use ($processor) {
    $processor->reset();
    $processor->processRawCommand($command);
    if ($processor->success()) {
        $result = $processor->getProcessingResult();
        if ($result != $expectedResult) {
            if (is_null($expectedResult) && !is_null($expectedError)) {
                echo "<strong>Test failed for command $command: result '" . var_export($result, true)
                    . "' did not fail with expected error '" . $expectedError . "'</strong><br/>";
            } else {
                echo "<strong>Test failed for command $command: result '" . var_export($result, true)
                    . "' did not match expected result '" . var_export($expectedResult, true) . "'</strong><br/>";
            }
            return false;
        }
        echo "Test passed for command $command: result matched expected result '" . var_export($expectedResult, true) . "'<br/>";
    } elseif (!is_null($expectedResult)) {
        echo "<strong>Test failed for command $command with error: " . $processor->getError() . "</strong><br/>";
        return false;
    } elseif (!is_null($expectedError)) {
        if ($expectedError != $processor->getError()) {
            echo "<strong>Test failed for command $command with unexpected error: " . $processor->getError() 
                . ". Expected " . $expectedError . "</strong><br/>";
            return false;
        } else {
            echo "Test passed for command $command: Failed with expected error '" . $processor->getError() . "'<br/>";
        }
    } else {
        echo "Test passed for command $command: Failed as intended<br/>";
    }
    return true;
};

$testCommand('help', $processor->getHelpText('<br/>'));
$testCommand('');
$testCommand('(');
$testCommand(')');
$testCommand('(3');
$testCommand('3)');
$testCommand('((3)');
$testCommand('(3()');
$testCommand('(3)(');
$testCommand('()3)');
$testCommand('(3))');
$testCommand(')(3)');
$testCommand('[');
$testCommand(']');
$testCommand('[3');
$testCommand('3]');
$testCommand('+[3]', null, "Too few operands provided for operator: +");
$testCommand('+[3,5]', null, "Too few operands provided for operator: +");
$testCommand('[3]+', null, "Too few operands provided for operator: +");
$testCommand('[3,5]+', null, "Too few operands provided for operator: +");
$testCommand('3[+5]', null, "Too few operations provided");
$testCommand('3[5+]', null, "Too few operations provided");
$testCommand('3[5]+', null, "Too few operations provided");
$testCommand('3[5]', null, "Too few operations provided");
$testCommand('[3][5]', null, "Too few operations provided");
$testCommand('3,5', null, "Unopened group detected");

$testCommand('3', 3);
$testCommand('PI', M_PI);
$testCommand('(3)', 3);
$testCommand('(3)(4)', 12);
$testCommand('3(4)', 12);
$testCommand('(3)4', 12);
$testCommand('((3)(4))', 12);
$testCommand('(3(4))', 12);
$testCommand('((3)4)', 12);
$testCommand('((3))', 3);
$testCommand('(((3)))', 3);
$testCommand('[3]', array(3));
$testCommand('[3,4,5]', array(3, 4, 5));
$testCommand('3+3', 6);
$testCommand('PI+PI', M_PI + M_PI);
$testCommand('[PI+PI]', array(M_PI + M_PI));
$testCommand('[PI,PI,PI]', array(M_PI, M_PI, M_PI));
$testCommand('1+2+3', 6);
$testCommand('3-2-1', 0);
$testCommand('3+2-1', 4);
$testCommand('3*2-1', 5);
$testCommand('3*(2-1)', 3);
$testCommand('(3*2-1)', 5);
$testCommand('d[3]', 3);
$testCommand('d[3,3,3]', 3);
$testCommand('d[[3],[3],[3]]', array(3));
$testCommand('max(1,PI,2,3)', M_PI);
$testCommand('min(1,PI,2,3)', 1);
$testCommand('max(1,[PI,2],3)', array(M_PI, 2));
$testCommand('min(1,[PI,2],3)', 1);
$testCommand('max(max(1,[PI,2],3))', M_PI);
$testCommand('min(max(1,[PI,2],3))', 2);
$testCommand('-min(max(1,[PI,2],3))', -2);
$testCommand('3-min(max(1,[PI,2],3))', 1);
$testCommand('min(max(1,[PI,2],3))+3', 5);
$testCommand('min(max(1,[PI,2],3))-3', -1);
$testCommand('--min(max(1,[PI,2],3))+3', 5);
$testCommand('----min(max(1,[PI,2],3))+3', 5);
$testCommand('-----min(max(1,[PI,2],3))+3', 1);
$testCommand('2!', 2);
$testCommand('3!', 6);
$testCommand('4!', 24);
$testCommand('1d[4]d[3]', 12);
$testCommand('d[5]+d[5]', 10);
$testCommand('d[5+5]', 10);
$testCommand('[5+5]', array(10));
$testCommand('[d1,d1]', array(1, 1));
$testCommand('[d1+4,d1]', array(5, 1));
$testCommand('[d1,d1+4]', array(1, 5));
$testCommand('[d1+4,d1+9]', array(5, 10));
$testCommand('[4d1+d1+5,d1+1+3d1]', array(10, 5));
$testCommand('[4d1,4!]', array(4, 24));
$testCommand('[4!,4d1]', array(24, 4));
$testCommand('[4!,4!]', array(24, 24));
$testCommand(DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT . 'd1', DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT);
$testCommand(DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT . 'd[3]', (DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT * 3));
$testCommand((DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT + 1) . 'd1', null,
    "A maximum of " . DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT . " dice may be rolled at a time ("
    . (DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT + 1) . " > " . DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT . ")");
$testCommand((DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT + 1) . 'd[3]', null,
    "A maximum of " . DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT . " dice may be rolled at a time ("
    . (DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT + 1) . " > " . DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT . ")");
$testCommand('2500d1', null, "A maximum of " . DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT
    . " dice may be rolled at a time (2500 > " . DiceRollSymbolRegistry::MAXIMUM_ALLOWED_DICE_COUNT . ")");

?></body>
</html>