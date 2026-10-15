<?php
namespace Clicalmani\Database\Events;

class QueryEvent
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
        public readonly float $timeMs = 0.0,
        public readonly bool $success = false,
        public readonly ?\Throwable $error = null,
    ) {}
}