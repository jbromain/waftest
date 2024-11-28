<?php 

namespace Touchweb\Waftest\tests;

/**
 * Définition des tests qui seront lancés par le moteur.
 * L'identifiant du test = son nom = le nom de la méthode.
 * 
 * Voir exemple aaaaa1_documentation pour les détails.
 */
class TestDefinitions {

    /**
     * Exemple. Ce test n'est pas réellement exécuté lors de l'audit.
     */
    public function aaaaa1_documentation(): array {
        
        // Le retour doit être un tableau, 
        // mais TOUS les champs sont optionnels et ont une valeur par défaut

        return [
            // URL, vaut / par défaut
            // Si la chaine contient la séquence {random_identifier}, elle est remplacée par une chaine alphanumérique aléatoire
            'query' => '/ma-page.php?{random_identifier}=toto',

            // Méthode, POST par défaut
            'method' => 'POST',

            // Données envoyées en POST, vaut vide par défaut, ignoré si méthode autre que POST
            // Si la chaine contient la séquence {random_identifier}, elle est remplacée par une chaine alphanumérique aléatoire
            'data' => '', 

            // Timeout en ms, par défaut 3 secondes. Un timeout est considéré comme un échec du test
            'timeout' => 3000,

            // En-têtes SUPPLEMENTAIRES de la requête. 
            // Par défaut contient une série de headers permettant de se faire passer pour un navigateur
            'headers' => [
                'X-Toto: 1',
                'Cookie: Yep=1;Yop=2'
            ],

            // True pour que les headers User-Agent, Accept, Accept-language, et gestion du cache
            // soient ajoutés automatiquement (cas général permettant d'imiter un navigateur)
            // Indiquer false pour maîtriser la totalité des headers envoyés via le paramètre 'headers'
            'default_headers' => true,

            // Codes de réponse HTTP signifiant que la protection est OK; par défaut 403 405 406
            'expected' => [403, 405, 406],

            // True pour interrompre l'audit si ce test échoue
            // (Conçu pour détecter les blocages IP: si un GET / échoue, inutile d'aller plus loin)
            'stop_on_failure' => false,

            // Description et conseils associés à ce test, html autorisé; vide par défaut
            'help' => ''
        ];
    }


    /**
     * Test témoin effectué en premier. En cas d'échec, les résultats de l'audit sont invalidés.
     * (Blocage IDS)
     */
    public function t001_control(): array {
        return [
            'method' => 'GET',
            'expected' => [200],
            'help' => "Test témoin, vérifie l'absence de blocage IDS",
            'stop_on_failure' => true
        ];
    }

    public function t002_php_injection(): array {
        return [
            'data' => '{random_identifier}=<?php 1;',
            'help' => "Ce test vérifie qu'on ne peut pas envoyer du PHP"
        ];
    }

    public function t003_no_user_agent(): array {
        return [
            'default_headers' => false,
            'method' => 'GET',
            'help' => "Vérifie que les requêtes sans User-Agent sont rejetées"
        ];
    }

    /**
     * Test témoin effectué en dernier. En cas d'échec, les résultats de l'audit sont invalidés.
     * (Blocage IDS)
     */
    public function t999_control(): array {
        return [
            'method' => 'GET',
            'expected' => [200],
            'help' => "Test témoin, vérifie l'absence de blocage IDS",
            'stop_on_failure' => true
        ];
    }
}