<?php

namespace Clicalmani\Database;

use Clicalmani\Core\Collection\CollectionInterface;
use Clicalmani\Database\Concerns\IsolationLevel;
use Clicalmani\Database\Concerns\IsolationScope;
use Clicalmani\Database\Concerns\Raw;
use Clicalmani\Database\Contracts\ConnectionManager;
use Clicalmani\Database\Contracts\PreparedStatementInterface;
use Clicalmani\Database\Contracts\QueryExecutorInterface;
use PDO;
use PDOStatement;

interface DBInterface
{
    public function executor(): QueryExecutorInterface;

    public function getPrefix(): string;

    public function getInstance(): QueryInterface;

    public function getPdo(): PDO;

    public function setPdo(PDO $pdo): void;

    public function query(string $sql, ?array $options = [], ?array $flags = []): ?PDOStatement;

    public function raw(string $expression): Raw;

    public function enableQueryLog(int $limit = 1000): void;

    public function disableQueryLog(): void;

    public function flushQueryLog(): void;

    public function getQueryLog(): array;

    public function execute(string $sql, array $params = []): bool;

    public function fetch(PDOStatement $statement, int $flag = PDO::FETCH_BOTH): mixed;

    public function fetchAll($statement, int $flag = PDO::FETCH_BOTH): mixed;

    public function getRow($statement, ?int $flag = PDO::FETCH_NUM): mixed;

    public function numRows(PDOStatement $statement): int;

    public function foundRows(): int;

    public function prepare(string $sql, array $options = []): PreparedStatementInterface;

    public function error(): string;

    public function errno(): int;

    public function lastInsertId(): int|string|null;

    public function free(PDOStatement $statement): ?bool;

    public function transaction(?\Closure $callback = null): mixed;

    public function transactionWith(IsolationLevel $level, \Closure $callback): mixed;

    public function beginTransaction(?\Closure $callback = null): mixed;

    public function deadlock(\Closure $callback, int $attemps = 5, int $sleep = 100): mixed;

    public function commit(): bool;

    public function rollback(): bool;

    public function inTransaction(): bool;

    public function savePoint(string $name): PDOStatement;

    public function rollbackTo(string $savepoint): PDOStatement;

    public function isolateTransaction(
        IsolationLevel $level,
        IsolationScope $scope = IsolationScope::NEXT_TRANSACTION,
    ): void;

    public function close(): void;

    public function table(array|string $tables): QueryInterface;

    public function select(string $sql, ?array $options = [], ?array $flags = []) : CollectionInterface;

    public function selectOne(string $sql, ?array $options = [], ?array $flags = []) : mixed;

    public function statement(string $sql, ?array $options = [], ?array $flags = []): PDOStatement;

    public function unprepared(string $sql): PDOStatement;

    public function connection(?string $driver = null): QueryInterface;

    public function swap(\PDO|QueryExecutorInterface|string $connection): mixed;

    public function restore(): void;

    public function withConnection(
        \PDO|QueryExecutorInterface|string $connection,
        callable $callback,
    ): mixed;

    public function reset(): void;
}