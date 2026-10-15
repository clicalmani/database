<?php
namespace Clicalmani\Database;

use Clicalmani\Core\Collection\CollectionInterface;
use Clicalmani\Core\Support\Facades\Log;
use Clicalmani\Database\Concerns\IsolationLevel;
use Clicalmani\Database\Concerns\IsolationScope;
use Clicalmani\Database\Concerns\Raw;
use Clicalmani\Database\Concerns\SqlBinder;
use Clicalmani\Database\Contracts\ConnectionManager;
use Clicalmani\Database\Contracts\QueryExecutorInterface;
use Clicalmani\Database\Execution\PdoQueryExecutor;
use Clicalmani\Database\Listeners\QueryListenerRegistry;
use PDO;
use PDOStatement;

/**
 * Database abstraction class
 * 
 * DB class use PHP Data Objects (PDO) extension interface for accessing database.
 * It uses MySQL database driver as its default driver. Other databases can be used 
 * by specifing the corresponding specific PDO driver.
 * 
 * @package Flesco\Database
 * @author @clicalmani
 */
abstract class DB implements DBInterface
{
	/**
	 * Stores database instance.
	 * 
	 * @var QueryInterface
	 */
	private $instance;

	private ?ConnectionManager $manager = null;

    private ?QueryExecutorInterface $executor = null;

	private array $queryLog = [];

	private bool $queryLogEnabled = false;

	private int $queryLogLimit = 1000;

	private $queryLogStop = null;

	/**
	 * Stores the current database connection
	 * 
	 * @var ?ConnectionManager
	 */
	protected ?ConnectionManager $connection = null;

	private int $transactionLevel = 0;

	private ?QueryListenerRegistry $listenerRegistry = null;

	protected function config(): array
    {
        return app()->config->database();
    }

	protected function manager(): ConnectionManager
    {
		if ($this->manager) return $this->manager;
        $this->manager = new ConnectionManager;
        $this->manager->configure($this->config());
		return $this->manager;
    }

	public function executor(): QueryExecutorInterface
    {
        return $this->executor ??= new PdoQueryExecutor(
            $this->manager(),
            $this->listenerRegistry(),
        );
    }
	
	public function getPrefix(): string
    {
        return env('DB_TABLE_PREFIX', '');
    }
	
	/**
	 * Returns the current database instance.
	 * @return QueryInterface
	 */
	public function getInstance() : QueryInterface
	{
		return $this->instance ??= new DBQuery(
			DBQuery::SELECT,
			[], [], $this->executor()
		);
	}

	/**
	 * Returns the current pdo instance.
	 * @return \PDO
	 */
	public function getPdo(): \PDO
    {
        return $this->manager()->pdo();
    }

	/**
	 * Set a PDO instance to be use for the next query.
	 * 
	 * @param \PDO $pdo A PDO object
	 */
	public function setPdo(\PDO $pdo): void
    {
        $this->manager()->setPdo($pdo);
        $this->executor = null; // force la recréation
		$this->instance = null;
    }
	
	/**
	 * Execute a statement request on the database.
	 * 
	 * @param string $sql The SQL code to execute
	 * @param ?array $options Parameters options
	 * @param ?array $flags Option flags
	 * @return \PDOStatement
	 */
	public function query(string $sql, ?array $options = [], ?array $flags = []): ?\PDOStatement
    {
        return $this->executor()->query($sql, $options, $flags);
    }

	public function raw(string $expression): Raw
	{
		return new Raw($expression);
	}

	/**
	 * Enable query log
	 * Statement generated SQL code will be logged starting from where the call is made.
	 * @return void
	 */
	public function enableQueryLog(int $limit = 1000): void
    {
        if ($this->queryLogEnabled) {
            return; // déjà activé
        }

        $this->queryLogEnabled = true;
        $this->queryLogLimit = $limit;
        $this->queryLog = [];

        // Enregistre UN listener qui alimente le buffer
        $this->queryLogStop = static::listen(function (
            string $sql,
            array $bindings,
            float $timeMs
        ) {
            $this->queryLog[] = [
                'sql'      => $sql,
                'bindings' => $bindings,
                'time_ms'  => $timeMs,
            ];

            // Tronque si on dépasse la limite (buffer circulaire)
            if (count($this->queryLog) > $this->queryLogLimit) {
                array_shift($this->queryLog);
            }
        });
    }

	public function disableQueryLog(): void
    {
        if ($this->queryLogStop !== null) {
            ($this->queryLogStop)();
            $this->queryLogStop = null;
        }
        $this->queryLogEnabled = false;
    }

	public function flushQueryLog(): void
	{
		$this->queryLog = [];
	}

	public function getQueryLog(): array
	{
		return $this->queryLog;
	}

	/**
	 * Execute a statement request.
	 * 
	 * @param string $sql
	 * @return int|false
	 */
	public function execute(string $sql, array $params = []): bool
    {
        return $this->executor()->execute($sql, $params);
    }

	/**
	 * Fetch rows
	 * 
	 * @param \PDOStatement $statement
	 * @param int $flag
	 * @return mixed
	 */
	public function fetch(\PDOStatement $statement, int $flag = PDO::FETCH_BOTH) : mixed
	{ 
		if ($statement instanceof PDOStatement) return $statement->fetch($flag);
		return null;
	}

	/**
	 * Fetch all rows from a result set.
	 * 
	 * @param \PDO::Statement $statement
	 * @param int \PDO Constant default is PDO::FETCH_BOTH
	 * @return mixed Result row on success, false on failure.
	 */
	public function fetchAll($statement, int $flag = PDO::FETCH_BOTH) : mixed
	{
		if ($statement instanceof PDOStatement) return $statement->fetchAll($flag);
		return [];
	}
	
	/**
	 * Retrieve a single row from the result
	 * 
	 * @param \PDOStatement $statement
	 * @param ?int $flag
	 * @return mixed
	 */
	public function getRow($statement, ?int $flag = PDO::FETCH_NUM) : mixed
	{
		if ($statement instanceof PDOStatement) return $statement->fetch($flag);
		return [];
	}
	
	/**
	 * Return the row count for the statement result.
	 * 
	 * @return int
	 */
	public function numRows(PDOStatement $statement) : int
	{ 
		if ($statement instanceof PDOStatement) return $statement->rowCount(); 
		return 0;
	}

	/**
	 * Return the number of found rows for a SQL statement
	 * 
	 * @return int
	 */
	public function foundRows() : int
	{
		return $this->getPdo()->query('SELECT FOUND_ROWS()')?->fetch(PDO::FETCH_NUM)[0] ?? 0;
	}

	/**
	 * Prepare a statement
	 * 
	 * @param string $sql
	 * @param ?array $options
	 * @return \PDOStatement
	 */
	public function prepare(string $sql, array $options = []): Contracts\PreparedStatementInterface
    {
        return $this->executor()->prepare($sql, $options);
    }
	
	/**
	 * Retrieve the error message
	 * 
	 * @return array
	 */
	public function error(): string
    {
        return $this->executor()->error();
    }
	
	/**
	 * Retrieve the error code
	 * 
	 * @return string
	 */
	public function errno(): int
    {
        return $this->executor()->errno();
    }
	
	/**
	 * Retrieve the last insert id
	 * 
	 * @return string|false
	 */
	public function lastInsertId(): int|string|null
    {
        return $this->executor()->lastInsertId();
    }

	/**
	 * Free a statement
	 * 
	 * @param \PDOStatement $statement Statement to free
	 * @return ?bool
	 */
	public function free(PDOStatement $statement) : ?bool
	{ 
		if ($statement instanceof PDOStatement) return $statement = null; 
		return false;
	}

	/**
	 * Begin a transaction
	 * 
	 * @param ?\Closure $callback
	 */
	public function transaction(?\Closure $callback = null) : mixed
	{
		$pdo = $this->getPdo();

		if ($callback === null) {
			if (!$pdo->inTransaction()) $pdo->beginTransaction();
			return $pdo;
		}

		$nested = $pdo->inTransaction();
		$savepoint = 'trans_sp_' . (++$this->transactionLevel);

		try {
			$nested ? $pdo->exec("SAVEPOINT $savepoint") : $pdo->beginTransaction();

			$result = $callback();

			if ($result !== false) {
				$nested ? $pdo->exec("RELEASE SAVEPOINT $savepoint") : $pdo->commit();
			} else {
				$nested ? $pdo->exec("ROLLBACK TO SAVEPOINT $savepoint") : $pdo->rollBack();
			}

			return $result;
		} catch (\Throwable $e) {
			if ($pdo->inTransaction()) {
				$nested ? $pdo->exec("ROLLBACK TO SAVEPOINT $savepoint") : $pdo->rollBack();
			}
			throw $e;
		} finally {
			$this->transactionLevel--;
		}
	}

	public function transactionWith(IsolationLevel $level, \Closure $callback) : mixed
	{
		$this->isolateTransaction($level);
		return $this->transaction($callback);
	}

	/**
	 * Begin a transaction
	 * Alias of transaction
	 * 
	 * @param ?\Closure $callback
	 * @return mixed
	 */
	public function beginTransaction(?\Closure $callback = null) : mixed
	{
		return $this->transaction($callback);
	}

	/**
	 * Handling deadlocks in a simultanous transactions by using a callback function.
	 * 
	 * @param \Closure $callback
	 * @param ?int $attemps Number of attemps to recover
	 * @param ?int $sleep Time delay for each attemp
	 * @return mixed
	 */
	public function deadlock(\Closure $callback, int $attemps = 5, int $sleep = 100) : mixed
	{
		while ($attemps--) {
			try {
				return $this->transaction($callback);
			} catch (\PDOException $e) {
				$isRealDeadlock = $e->getCode() === '40001' 
					|| str_contains($e->getMessage(), 'Deadlock found');

				if ( ! $isRealDeadlock || $attemps === 0 ) {
					throw $e;
				}
				usleep($sleep);
			}
		}

		return null;
	}

	/**
	 * Commit statements
	 * 
	 * @return bool
	 */
	public function commit() : bool { 
		if ($this->inTransaction()) return !!$this->getPdo()?->commit(); 
		return false;
	}

	/**
	 * Rollback back statements
	 * 
	 * @return bool
	 */
	public function rollback() : bool { 
		if ($this->inTransaction()) return !!$this->getPdo()?->rollback(); 
		return false;
	}

	/**
	 * Verify if a transaction is already running.
	 * 
	 * @return bool
	 */
	public function inTransaction() : bool { return !!$this->getPdo()?->inTransaction(); }

	/**
	 * Savepoint statement: Create a savepoint to rollback to.
	 * 
	 * @param string $name The savepoint name
	 * @return \PDOStatement
	 */
	public function savePoint(string $name) : \PDOStatement { return $this->statement("SAVEPOINT $name"); }

	/**
	 * Rollback to save point
	 * 
	 * @param string $savePoint 
	 * @see savePoint
	 */
	public function rollbackTo(string $savepoint) : \PDOStatement { return $this->statement("ROLLBACK TO SAVEPOINT $savepoint"); }
	
	/**
	 * Définit le niveau d'isolation d'une transaction.
	 *
	 * ⚠️ MySQL : SET TRANSACTION ISOLATION LEVEL sans GLOBAL/SESSION ne
	 * s'applique qu'à la PROCHAINE transaction. Utilisez SESSION pour un
	 * changement persistant dans la session courante.
	 *
	 * ⚠️ Doit être appelé AVANT beginTransaction() quand le scope est vide.
	 *
	 * @param IsolationLevel $level Niveau d'isolation désiré
	 * @param IsolationScope $scope Portée (défaut : prochaine transaction)
	 *
	 * @throws \RuntimeException Si une transaction est en cours avec scope vide
	 * @throws \PDOException En cas d'erreur SQL
	 */
	public function isolateTransaction(
		IsolationLevel $level,
		IsolationScope $scope = IsolationScope::NEXT_TRANSACTION,
	): void {
		$pdo = $this->getPdo();

		if ($scope === IsolationScope::NEXT_TRANSACTION && $pdo->inTransaction()) {
			throw new \RuntimeException(
				"SET TRANSACTION ISOLATION LEVEL doit être appelé AVANT beginTransaction()."
			);
		}

		$sql = 'SET ';

		if ($scope !== IsolationScope::NEXT_TRANSACTION) {
			$sql .= $scope->value . ' ';
		}

		$sql .= 'TRANSACTION ISOLATION LEVEL ' . $level->value;
		
		$result = $pdo->exec($sql);

		if ($result === false) {
			$error = $pdo->errorInfo();
			throw new \PDOException(sprintf(
				"Échec de définition du niveau d'isolation : [%s] %s",
				$error[0] ?? '?',
				$error[2] ?? 'unknown error'
			));
		}
	}
	
	public function close() : void { $this->executor = null; $this->instance = null; }

	public function table(array|string $tables): QueryInterface
	{
		$instance = new DBQuery(DBQuery::SELECT, [], [], $this->executor());

		$instance->set('tables', is_string($tables) ? [$tables] : $tables);

		return $instance;
	}

	public function select(string $sql, ?array $options = [], ?array $flags = []) : CollectionInterface
	{
		$statement = $this->statement($sql, $options, $flags);
		return collect($statement->fetchAll(...$flags));
	}

	public function selectOne(string $sql, ?array $options = [], ?array $flags = []) : mixed
	{
		$statement = $this->statement($sql, $options, $flags);
		$row = $statement->fetch();
		return $row ?: null;
	}

	public function statement(string $sql, ?array $options = [], ?array $flags = []) : PDOStatement
	{
		$statement = $this->prepare($sql, $flags);
		$statement->execute($options);
		return $statement->unwrap();
	}

	public function unprepared(string $sql) : PDOStatement
	{
		return $this->getPdo()->query($sql);
	}

	public function connection(?string $driver = null): QueryInterface
    {
        if ($driver !== null) {
            $this->connection = null; // force la recréation
            $this->manager()->configure($this->config()); // recharge
            $this->connection = new ConnectionManager;
            $this->connection->configure($this->config());
        }

        return new DBQuery(
			DBQuery::SELECT,
			[], [], $this->executor()
		);
    }

	public function swap(\PDO|QueryExecutorInterface|string $connection): mixed
    {
		$this->instance = null;

        // Cas 1 : executor custom (fake de test)
        if ($connection instanceof QueryExecutorInterface) {
            $previous = $this->executor;
            $this->executor = $connection;
            return $previous;
        }

        // Cas 2 : PDO ou nom de connexion → on swappe la connexion
        $previous = $this->manager()->pdo();

        $pdo = match (true) {
            $connection instanceof \PDO => $connection,
            is_string($connection)       => $this->manager()->forConnection($connection),
            default                      => throw new \InvalidArgumentException(
                "DB::swap() attend un \\PDO, un QueryExecutorInterface ou un nom de connexion."
            ),
        };

        $this->manager()->swap($pdo);
        $this->executor = null; // force la recréation avec la nouvelle connexion

        return $previous;
    }

	public function restore(): void
    {
        $this->manager()->restore();
		$this->instance = null;
        $this->executor = null;
    }

	public function withConnection(
        \PDO|QueryExecutorInterface|string $connection,
        callable $callback,
    ): mixed {
        $this->swap($connection);

        try {
            return $callback();
        } finally {
            $this->restore();
        }
    }

	public function reset(): void
    {
        $this->connection = null;
        $this->executor   = null;
		$this->instance   = null;
        $this->listenerRegistry = null;
    }

	/**
	 * Enregistre un listener appelé à chaque requête SQL exécutée.
	 *
	 * @param callable $listener function(string $sql, array $bindings, float $timeMs): void
	 * @return callable Callable de désinscription : appelez-le pour retirer le listener.
	 */
	public static function listen(callable $listener): callable
	{
		return static::listenerRegistry()->register($listener);
	}

	/**
	 * Exécute $callback en capturant toutes les requêtes SQL émises.
	 *
	 * @param callable $callback    Code à exécuter
	 * @param callable|null $onDone function(array $queries): void — reçoit la liste des requêtes
	 * @return mixed Le retour de $callback
	 */
	public static function captureQueries(callable $callback, ?callable $onDone = null): mixed
	{
		$queries = [];

		$stop = static::listen(function (string $sql, array $bindings, float $timeMs) use (&$queries) {
			$queries[] = [
				'sql'      => $sql,
				'bindings' => $bindings,
				'time_ms'  => $timeMs,
			];
		});
		
		try {
			return $callback();
		} finally {
			$stop();

			if ($onDone !== null) {
				$onDone($queries);
			}
		}
	}

	public function getDriverName(): mixed
	{
		return $this->manager()->pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	protected static function listenerRegistry(): QueryListenerRegistry
    {
        return QueryListenerRegistry::getInstance();
    }
}
