<?php
namespace Clicalmani\Database\Execution;

use Clicalmani\Database\Contracts\PreparedStatementInterface;
use Clicalmani\Database\Contracts\QueryExecutorInterface;
use Clicalmani\Database\Contracts\ConnectionManager;
use Clicalmani\Database\Events\QueryEvent;
use Clicalmani\Database\Listeners\QueryListenerRegistry;

final class PdoQueryExecutor implements QueryExecutorInterface
{
    public function __construct(
        private ConnectionManager $connection,
        private QueryListenerRegistry $listeners,
    ) {}

    public function query(string $sql, array $options = [], array $flags = []): ?\PDOStatement
    {
        $start = hrtime(true);

        try {
            $stmt = $this->connection->pdo()->prepare($sql, $flags);
            $stmt?->execute($options);

            $this->emit($sql, $options, $start, $stmt !== false);

            return $stmt ?: null;
        } catch (\PDOException $e) {
            $this->emit($sql, $options, $start, false, $e);
            throw $e;
        }
    }

    public function execute(string $sql, array $params = []): bool
    {
        $start = hrtime(true);

        try {
            $result = $this->connection->pdo()->exec($sql);

            $this->emit($sql, $params, $start, $result !== false);

            return $result !== false;
        } catch (\PDOException $e) {
            $this->emit($sql, $params, $start, false, $e);
            throw $e;
        }
    }

    public function prepare(string $sql, array $options = [], array $bindings = []): PreparedStatementInterface
    {
        try {
            $stmt = $this->connection->pdo()->prepare($sql, $options);

            if (!$stmt) {
                throw new \RuntimeException("PDO::prepare() a échoué pour : $sql");
            }

            return new PdoPreparedStatement($stmt, $sql, $this->listeners);
        } catch (\Throwable $e) {
            $this->emit($sql, $bindings, hrtime(true), false, $e);
            throw $e;
        }
    }

    public function numRows(?\PDOStatement $stmt): int
    {
        return $stmt ? $stmt->rowCount() : 0;
    }

    public function foundRows(): int
    {
        $stmt = $this->connection->pdo()->query('SELECT FOUND_ROWS()');
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }

    public function fetch(\PDOStatement $stmt, int $mode = \PDO::FETCH_OBJ): mixed
    {
        return $stmt->fetch($mode);
    }

    public function lastInsertId(): int|string|null
    {
        $id = $this->connection->pdo()->lastInsertId();

        return ($id === false || $id === '') ? null : $id;
    }

    public function errno(): int
    {
        return (int) ($this->connection->pdo()->errorInfo()[1] ?? 0);
    }

    public function error(): string
    {
        return (string) ($this->connection->pdo()->errorInfo()[2] ?? '');
    }

    // -----------------------------------------------------------------
    //  Interne
    // -----------------------------------------------------------------

    private function emit(
        string $sql,
        array $bindings,
        int $startNs,
        bool $success,
        ?\Throwable $error = null,
    ): void {
        $this->listeners->dispatch(new QueryEvent(
            sql:      $sql,
            bindings: $bindings,
            timeMs:   (hrtime(true) - $startNs) / 1_000_000,
            success:  $success,
            error:    $error,
        ));
    }
}