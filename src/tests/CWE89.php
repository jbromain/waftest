<?php

namespace Touchweb\Waftest\tests;

/**
 * Définition des tests qui seront lancés par le moteur.
 * L'identifiant du test = son nom = le nom de la méthode.
 * 
 * Les tests seront exécutés dans l'ordre alphabétique des noms de méthodes.
 * 
 */
class CWE89 {
    public function t001_sqli(): array {
        return [
            'data' => "{random_identifier}={random_identifier}'--&p={random_identifier}",
            'help' => 'Test de SQL Injection',
        ];
    }

}