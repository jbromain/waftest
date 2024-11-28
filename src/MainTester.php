<?php

namespace Touchweb\Waftest;

use Exception;
use InvalidArgumentException;
use LogicException;
use Touchweb\Waftest\tests\CWE89;
use Touchweb\Waftest\tests\TestDefinitions;

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
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Accept-Encoding: gzip, deflate, br, zstd',
            'Upgrade-Insecure-Requests: 1',
            'Priority: u=0, i'
        ],
        'default_headers' => true,
        'expected' => [403, 405, 406],
        'stop_on_failure' => false,
        'help' => ''
    ];

    /**
     * URL de la page d'accueil du site à tester, sans le / final.
     */
    private string $homeURL;

    /**
     * Définition des classes de tests, les tests seront exécutés dans l'ordre.
     * Instancié à la construction de la classe.
     * 
     * Clé = nom de la classe, sans le namespace
     * Valeur = instance de la classe
     */
    private array $testsDefinitions;

    public function __construct()
    {
        $this->testsDefinitions = [
            'TestDefinitions' => new TestDefinitions(),
            'CWE89' => new CWE89()
        ];
    }

    /**
     * Lance le test dont le nom est fourni.
     * Déclenche une exception si le nom du test n'existe pas, ou que l'url du site n'a pas été préalablement configurée.
     * Retourne le résultat du test, sous la forme d'un tableau
     * [
     *   'pass' => boolean,
     *   'http_status' => ?int,
     *   'timeout' => boolean,
     *   'error_msg' => ?string
     * ]
     * 
     * @param string $testFullName Nom du test à lancer, sous la forme CLASSE-METHODE. Exemple: CWE89-t001_sqli
     */
    public function run(string $testFullName): array
    {
        // Décomposition du nom du test
        if(strpos($testFullName, '-') === false){
            throw new InvalidArgumentException("Bad test name format: $testFullName. Exptected: CLASS-METHOD. Example: CWE89-t001_sqli");
        }
        list($testClassName, $testName) = explode('-', $testFullName);
        
        if(! isset($this->testsDefinitions[$testClassName])){
            throw new InvalidArgumentException("Test class does not exist: $testClassName");
        }
        
        if (! method_exists($this->testsDefinitions[$testClassName], $testName)) {
            throw new InvalidArgumentException("Test does not exist: $testName");
        }

        if (! isset($this->homeURL)) {
            throw new LogicException("You must specify website URL first");
        }

        $testParams = $this->computeParameters($this->testsDefinitions[$testClassName]->$testName());

        return $this->executeQuery($testParams);
    }

    /**
     * Lance une requête CURL pour exécuter un test avec les paramètres fournis.
     */
    private function executeQuery(array $testParams){
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

        // Mode debug (très verbeux, affiche les headers échangés)
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        
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
            //print($res);

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
     * Retourne la liste de tous les noms de tests connus.
     * Les tests sont retournés dans l'ordre de déclaration des classes de tests, puis dans l'ordre
     * alphabétique des noms de tests au sein d'une classe.
     * 
     * Le format des CLASSE-METHODE. Exemple: CWE89-t001_sqli
     * 
     * @return string[]
     */
    public function getAllTestNames(): array {
        // On parcourt les instances des classes de tests
        $res = [];
        foreach($this->testsDefinitions as $className => $def){
            $res = array_merge($res, $this->getAllTestNamesFromClass($def));
        }

        return $res;
    }

    /**
     * Retournes les noms de tests d'une classe donnée.
     * Le résultat est sous la forme d'un tableau dont chaque élément est
     * le nom d'un test. 
     * 
     * Le nom d'un test est sous la forme CLASSE-METHODE. Exemple: CWE89-t001_sqli
     * Le nom de la classe ne contient pas le namespace.
     */
    private function getAllTestNamesFromClass($def): array {
        $res = get_class_methods($def);
        $res = array_diff($res, [
            '__construct',
            'aaaaa1_documentation'
        ]);
        sort($res);

        // Nom de la classe sans le namespace
        $className = array_search($def, $this->testsDefinitions);
        $res = array_map(function($name) use ($className){
            return $className . '-' . $name;
        }, $res);
        return $res;
    }

    /**
     * Retourne s'il existe un test portant le nom indiqué.
     * 
     * @param string $testName Nom du test
     * @return bool Vrai si le test existe
     */
    public function testExists(string $testName): bool {
        return in_array($testName, $this->getAllTestNames());
    }

    /**
     * Retourne un tableau descriptif des tests, d'un point de vue fonctionnel.
     * Destiné à l'affichage. Les tests sont retournés dans l'ordre d'exécution recommandée.
     * Chaque entrée du tableau contient un test sous cette forme:
     * [
     *   name => Nom du test, sous la forme CLASSES-METHODE. Exemple: CWE89-t001_sqli
     *   help => Description si présente, peut contenir du HTML
     *   stop_on_failure => true si l'audit doit être stoppé si ce test échoue, false sinon
     * ]
     * 
     */
    public function getTestsDefinitions(): array {
        $res = [];
        $testNames = $this->getAllTestNames();
        foreach ($testNames as $name) {
            list($className, $methodName) = explode('-', $name);
            $def = $this->testsDefinitions[$className];

            $testParams = $this->computeParameters($def->$methodName());
            $myTest = [
                'name' => $name,
                'help' => $testParams['help'],
                'stop_on_failure' => $testParams['stop_on_failure']
            ];
            $res[] = $myTest;
        }
        return $res;
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
        $res = self::getDefaultsTestOptions();
        if (! is_array($specific)) {
            throw new Exception("Bad test definition - Not an array");
        }

        // Cas particulier: suppression des headers par défaut
        if(isset($specific['default_headers']) && $specific['default_headers'] === false){
            $res['headers'] = [];
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

        // Identifiants aléatoires dans les données POST
        // TODO améliorer la chaine aléatoire pour que ça ait l'air francais
        // max 2 consonnes à la suite, max 3 voyelles à la suite
        $random = '';


        while(($pos = strpos($res['data'], '{random_identifier}')) !== false){
            $random = $this->generateRandomIdentifier();
            $res['data'] = substr_replace($res['data'], $random, $pos, strlen('{random_identifier}'));
        }

        // Identifiants aléatoires dans la query GET
        while(($pos = strpos($res['query'], '{random_identifier}')) !== false){
            $random = $this->generateRandomIdentifier();
            $res['query'] = substr_replace($res['query'], $random, $pos, strlen('{random_identifier}'));
        }
        
        return $res;
    }

    /**
     * Génère un identifiant aléatoire de 2 à 10 caractères alphabétiques,
     * avec au max 2 consommes consécutives et 3 voyelles consécutives.
     * 
     * @return string Identifiant aléatoire
     */
    private function generateRandomIdentifier(): string {
        $consonnes = 'bcdfghjklmnpqrstvwxz';
        $voyelles = 'aeiouy';
        $res = '';
        $nb = random_int(2, 10);
        $voyelle = random_int(0, 1) === 0;
        for($i = 0; $i < $nb; $i++){
            if($voyelle){
                $res .= $voyelles[random_int(0, strlen($voyelles) - 1)];
                if(random_int(0, 2) === 0){
                    $voyelle = false;
                }
            } else {
                $res .= $consonnes[random_int(0, strlen($consonnes) - 1)];
                if(random_int(0, 1) === 0){
                    $voyelle = true;
                }
            }
        }
        return $res;
    }
}
