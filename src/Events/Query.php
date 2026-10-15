<?php
namespace Clicalmani\Database\Events;

class Query
{
    /**
     * @param string $sql       Requête exécutée
     * @param array  $bindings  Paramètres liés
     * @param float  $duration  Durée en millisecondes
     * @param array  $profile   Résultat de SHOW PROFILE (vide si non profilé)
     * @param string $connection Nom de la connexion
     */
    public function __construct(
        public readonly string $sql,
        public readonly array $bindings = [],
        public readonly float $duration = 0.0,
        public readonly array $profile = [],
        public readonly string $connection = 'default',
    ) {}
}