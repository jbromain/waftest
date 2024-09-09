<?php

namespace Realdev\Waftest;

use Exception;
use InvalidArgumentException;
use LogicException;

/**
 * Classe principale, permet de lancer un test et de retourner le résultat.
 */
class MainTester
{
    /**
     * Configuration par défaut des tests. Ces valeurs peuvent être surchargées par chaque test.
     * 
     * Seule particularité: les headers spécifiés par les tests sont ajoutés à ceux par défaut, ou écrasent
     * celui par défaut s'il était spécifié, mais ne suppriment pas le reste.
     */
    private static array $defaults = [
        'query' => '/',
        'method' => 'POST',
        'data' => '',
        'timeout' => 3000,
        'headers' => [
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'accept-language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7,la;q=0.6',
            'cache-control: no-cache',
            'pragma: no-cache',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36'
        ],
        'expected' => [403, 405, 406],
        'help' => ''
    ];

    /**
     * URL de la page d'accueil du site à tester, sans le / final.
     */
    private string $homeURL;

    private TestDefinitions $def;

    public function __construct()
    {
        $this->def =  new TestDefinitions();
    }

    /**
     * Lance le test dont le nom est fourni (doit correspondre à une méthode de la classe TestDefinitions).
     * Déclenche une exception si le nom du test n'existe pas, ou que l'url du site n'a pas été préalablement configurée.
     * Retourne le résultat du test, sous la forme d'un tableau
     * [
     *   'pass' => boolean,
     *   'http_status' => ?int,
     *   'timeout' => boolean,
     *   'error_msg' => ?string
     * ]
     */
    public function run(string $testName): array
    {
        if (! method_exists(TestDefinitions::class, $testName)) {
            throw new InvalidArgumentException("Test does not exist: $testName");
        }

        if (! isset($this->homeURL)) {
            throw new LogicException("You must specify website URL first");
        }

        $testParams = $this->computeParameters($this->def->$testName());

        return $this->executeQuery($testParams);
    }

    /**
     * Lance une requête CURL pour exécuter un test avec les paramètres fournis.
     */
    public function executeQuery(array $testParams){
        $ch = curl_init($this->homeURL . $testParams['query']);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, (int) $testParams['timeout']);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, (int) $testParams['timeout']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $testParams['method']);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);

        if($testParams['method'] === 'POST' && $testParams['data'] !== ''){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $testParams['data']);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $testParams['headers']);
        
        $res = curl_exec($ch);
        if($res === false || curl_errno($ch) != 0){
            // Erreur CURL
            if(in_array(curl_errno($ch), [CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED])){
                // Timeout
                $testResult = [
                    'pass' => false,
                    'timeout' => true,
                    'error_msg' => curl_error($ch)
                ];
            } else {
                // Autre erreur CURL
                $testResult = [
                    'pass' => false,
                    'timeout' => false,
                    'error_msg' => curl_error($ch)
                ];
            }
        } else {
            // Requête réussie du point de vue HTTP
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if(in_array($status, $testParams['expected'])){
                // Réponse http conforme aux attentes
                $testResult = [
                    'pass' => true,
                    'timeout' => false,
                    'http_status' => $status
                ];
            } else {
                // Réponse http non conforme aux attentes
                $testResult = [
                    'pass' => false,
                    'timeout' => false,
                    'http_status' => $status
                ];
            }
        }
        
        curl_close($ch);
        return $testResult;
    }


    /**
     * Définit l'url de la page d'accueil du site à tester, sans le / final.
     *
     * @return self
     */
    public function setHomeURL(string $homeURL)
    {
        $this->homeURL = $homeURL;

        return $this;
    }

    /**
     * Retourne la valeur par défaut des options des tests, pour info.
     */
    public function getDefaultsTestOptions(): array
    {
        return self::$defaults;
    }


    /**
     * Merge les paramètres spécifiques d'un test avec les paramètres par défaut,
     * retourne les paramètres réels à utiliser.
     * 
     * Remplace le cas échéant les placeholder par des noms aléatoires.
     * 
     * @param array $specific Paramètres spécifiques d'un test
     * @return array Paramètres compilés
     */
    private function computeParameters(array $specific): array
    {
        $res = self::$defaults;
        if (! is_array($specific)) {
            throw new Exception("Bad test definition - Not an array");
        }

        foreach ($res as $key => $value) {
            if (! isset($specific[$key])) {
                // Paramètre non modifié pour ce test
                continue;
            }

            if ($key == 'headers') {
                // Cas particulier des headers, on ajoute
                $res['headers'] = array_merge($value, $specific[$key]);
            } else {
                // Cas général, on remplace 
                $res[$key] = $specific[$key];
            }
        }

        while(($pos = strpos($res['data'], '{random_identifier}')) !== false){
            $random = bin2hex(random_bytes(5));
            $res['data'] = substr_replace($res['data'], $random, $pos, strlen('{random_identifier}'));
        }

        while(($pos = strpos($res['query'], '{random_identifier}')) !== false){
            $random = bin2hex(random_bytes(5));
            $res['query'] = substr_replace($res['query'], $random, $pos, strlen('{random_identifier}'));
        }
        

        return $res;
    }
}
