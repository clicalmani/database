<?php
namespace Clicalmani\Database\Contracts;

interface PreparedStatementInterface
{
    /**
     * Bind une valeur (nommée ou positionnelle).
     */
    public function bind(string|int $param, mixed $value, int $type = \PDO::PARAM_STR): static;

    /**
     * Bind plusieurs valeurs d'un coup.
     * @param array<string|int, mixed> $bindings
     */
    public function bindAll(array $bindings): static;

    /**
     * Exécute le statement.
     */
    public function execute(?array $params = null): bool;

    /**
     * Accès au PDOStatement sous-jacent (pour fetch).
     */
    public function unwrap(): ?\PDOStatement;
}