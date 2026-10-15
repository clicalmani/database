<?php
namespace Clicalmani\Database\Execution;

use Clicalmani\Database\Contracts\QueryExecutorInterface;

final class FakeQueryExecutor implements QueryExecutorInterface
{
    /** @var array<int, array<string,mixed>> */
    public array $rows = [];
    public array $log = [];
    public bool $shouldFail = false;

    public function query(string $sql, array $options = [], array $flags = []): ?\PDOStatement
    {
        $this->log[] = ['query', $sql, $options];

        if ($this->shouldFail) {
            throw new \PDOException('Fake failure');
        }

        // On retourne un PDOStatement factice (mock léger)
        return $this->makeFakeStatement();
    }

    public function execute(string $sql, array $params = []): bool
    {
        $this->log[] = ['execute', $sql];
        return !$this->shouldFail;
    }

    public function numRows(?\PDOStatement $stmt): int
    {
        return count($this->rows);
    }

    public function foundRows(): int
    {
        return count($this->rows);
    }

    public function fetch(\PDOStatement $stmt, int $mode = \PDO::FETCH_OBJ): mixed
    {
        static $i = 0;
        if (!isset($this->rows[$i])) {
            $i = 0;
            return false;
        }
        $row = (object) $this->rows[$i];
        $i++;
        return $row;
    }

    public function lastInsertId(): int|string|null { return 1; }
    public function errno(): int { return $this->shouldFail ? 1 : 0; }
    public function error(): string { return $this->shouldFail ? 'Fake failure' : ''; }

    private function makeFakeStatement(): \PDOStatement
    {
        // Astuce : PDOStatement n'est pas instanciable directement.
        // On peut retourner un mock PHPUnit, ou null si non-fetchable.
        // Pour simplifier, retourne null ici et gère dans hydrateResult().
        return new class extends \PDOStatement {
            public function __construct() {}
        };
    }
}