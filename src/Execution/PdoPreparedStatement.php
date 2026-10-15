<?php
namespace Clicalmani\Database\Execution;

use Clicalmani\Database\Contracts\PreparedStatementInterface;
use Clicalmani\Database\Events\QueryEvent;
use Clicalmani\Database\Listeners\QueryListenerRegistry;

final class PdoPreparedStatement implements PreparedStatementInterface
{
    private array $currentBindings = [];

    public function __construct(
        private \PDOStatement $stmt,
        private string $sql,
        private QueryListenerRegistry $listeners,
    ) {}

    public function bind(string|int $param, mixed $value, int $type = \PDO::PARAM_STR): static
    {
        // PDO attend ':name' pour les nommés, 1-based pour les positionnels
        $key = is_int($param) ? $param : ':' . ltrim($param, ':');
        $this->stmt->bindValue($key, $value, $type);
        $this->currentBindings[$key] = $value;
        return $this;
    }

    public function bindAll(array $bindings): static
    {
        foreach ($bindings as $key => $entry) {
            if (is_array($entry)) {
                [$value, $type] = $entry + [null, \PDO::PARAM_STR];
                $this->bind($key, $value, $type);
            } else {
                $this->bind($key, $entry);
            }
        }
        return $this;
    }

    public function execute(?array $params = null): bool
    {
        $start = microtime(true);

        try {
            $result = $this->stmt->execute($params);

            $this->listeners->dispatch(
                new QueryEvent(
                    sql:      $this->sql, 
                    bindings: $this->currentBindings, 
                    timeMs:   (microtime(true) - $start) * 1000,
                    success:  true,
                )
            );

            return $result;
        } catch (\Throwable $e) {
            $this->listeners->dispatch(
                new QueryEvent(
                    sql:      $this->sql, 
                    bindings: $this->currentBindings, 
                    timeMs:   (microtime(true) - $start) * 1000,
                    success: false,
                    error:   $e
                )
            );
            throw $e;
        }
    }

    public function unwrap(): ?\PDOStatement
    {
        return $this->stmt;
    }
}