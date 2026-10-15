<?php
namespace Clicalmani\Database\Contracts;

interface QueryExecutorInterface
{
    public function query(string $sql, array $options = [], array $flags = []): ?\PDOStatement;
    public function execute(string $sql): bool;
    public function prepare(string $sql, array $options = [], array $bindings = []): PreparedStatementInterface;
    public function numRows(?\PDOStatement $stmt): int;
    public function foundRows(): int;
    public function fetch(\PDOStatement $stmt, int $mode = \PDO::FETCH_OBJ): mixed;
    public function lastInsertId(): int|string|null;
    public function errno(): int;
    public function error(): string;
}