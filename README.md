# WAF Test: outil de test de pare-feu applicatif (Web Application Firewall)

## Objectifs
Cet outil en ligne de commande lance des requêtes HTTP vers un site web, contenant des charges (inoffensives), afin de vérifier si le site est correctement protégé par un pare-feu applicatif (WAF). 

Pour chaque test (chaque requête), un résultat est attendu, sous la forme d'un ou plusieurs codes réponse HTTP. Si le serveur répond bien avec l'un de ces codes, le site est considéré protégé pour ce schéma d'attaque.

## Pré-requis
Requiert PHP >= 8.3 avec CURL.
Non testé avec PHP < 8.3

## Utilisation
### Installation
```
git clone https://github.com/jbromain/waftest.git
cd waftest/
composer install
```

### Exécution d'un test unique
Le nom d'un test est le nom de la méthode qui le définit, dans la classe Realdev\Waftest\TestDefinitions.
```
php run-test.php https://www.google.fr t001_home_accessible
```

### Exécution successive de tous les tests
```
php run-test.php https://www.google.fr ALL
```

### Aide
```
php run-test.php
```

## Clause de non-responsabilité
Le logiciel est fourni "tel quel", sans garantie d'aucune sorte, expresse ou implicite. En aucun cas, les auteurs ou contributeurs ne pourront être tenus responsables de tout dommage, direct ou indirect, résultant de l'utilisation du logiciel. 

Vous utilisez ce logiciel à vos propres risques et responsabilités. Il vous incombe de tester et d'évaluer sa compatibilité avec vos besoins et systèmes avant toute utilisation.

Ce logiciel est conçu pour les propriétaires de sites web, il n'est pas destiné à être utilisé pour mesurer le niveau de sécurité de sites tiers. Il vous incombe d'utiliser ce logiciel dans le respect de toutes les lois et règlements applicables.