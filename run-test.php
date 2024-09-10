<?php

/**
 * Lanceur CLI
 */

use Realdev\Waftest\MainTester;
require 'vendor/autoload.php';

if (PHP_SAPI != "cli") {
    echo "Not in CLI";
    exit;
}


if ($argc != 3) {
    echo "USAGE:\n";
    echo "  php run-test URL TESTNAME\n\n";
    echo "WITH:\n";
    echo "  URL = Homepage URL of the website you want to test (WITH protocol but WITHOUT trailing slash)\n";
    echo "  TESTNAME = Name of the test to run (function name in the definition class). Use ALL to launch all the tests successively\n\n";
    exit;
}


$tester = new MainTester();
$tester->setHomeURL($argv[1]);

if($argv[2] !== 'ALL'){
    // Lancement d'un seul test
    runOneTest($tester, $argv[1], $argv[2]);

} else {
    // Lancement successif de tous les tests
    $testNames = $tester->getAllTestNames();
    foreach ($testNames as $testName) {
        runOneTest($tester, $argv[1], $testName);
    }
}


/**
 * Utilitaire; lance un test et affiche le résultat sur la console.
 */
function runOneTest(MainTester $tester, string $homeUrl, string $testName){
    echo "Running test $testName on website $homeUrl... ";

    $res = $tester->run($testName);
    if($res['pass']){
        echo "PASS (http ".$res['http_status'].")\n";
    } else {
        echo "FAIL (";
        if($res['timeout']) echo "timeout";
        else if(isset($res['http_status'])) echo $res['http_status'];
        else if(isset($res['error_msg'])) echo $res['error_msg'];
        else echo "failed silently";
        echo ")\n";
    }
}