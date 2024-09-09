<?php 

namespace Realdev\Waftest;

/**
 * Définition des tests qui seront lancés par le moteur.
 * L'identifiant du test = son nom = le nom de la méthode.
 * 
 * Voir exemple.
 */
class TestDefinitions {

    public function aaaaa1_documentation(): array {
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

            // En-têtes supplémentaires de la requête. Par défaut contient une série de headers permettant de se faire passer pour un navigateur
            'headers' => [
                'X-Toto: 1',
                'Cookie: Yep=1;Yop=2'
            ],

            // Codes de réponse HTTP signifiant que la protection est OK; par défaut 403 405 406
            'expected' => [403, 405, 406],

            // Description et conseils associés à ce test, html autorisé; vide par défaut
            'help' => ''
        ];
    }


    public function t1_home_accessible(): array {
        return [
            'method' => 'GET',
            'expected' => [200],
            'headers' => ['X-toto: 1'],
            'help' => "Ce test vérifie que la page d'accueil est accessible (IP du testeur non bloquée)"
        ];
    }

    public function t2_php_injection(): array {
        return [
            'data' => '{random_identifier}=<?php 1;',
            'help' => "Ce test vérifie qu'on ne peut pas envoyer du PHP"
        ];
    }
}