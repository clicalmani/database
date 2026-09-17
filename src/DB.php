<?php
namespace Clicalmani\Database;

use Clicalmani\Core\Support\Facades\Log;
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
	 * Permit dirty reads
	 * 
	 * @var int
	 */
	const TRANSACTION_DIRTY_READS = 0x0;

	/**
	 * Permit non repeatable reads
	 * 
	 * @var int
	 */
	const TRANSACTION_NON_REPEATABLE_READS = 0x0;

	/**
	 * Permit phantom reads
	 * 
	 * @var int
	 */
	const TRANSACTION_PHANTOM_READS = 0x1;

	/**
	 * Stores database instance.
	 * 
	 * @var QueryInterface
	 */
	private static $instance;

	/**
	 * Stores PDO instance
	 * 
	 * @var \PDO
	 */
	private static $pdo;

	/**
	 * Database tables prefix
	 * 
	 * @var string
	 */
	private static string $prefix = '';

	/**
	 * Toggle query log
	 * When enabled, all the queries will be logged.
	 * 
	 * @var bool
	 */
	private static bool $logQuery = false;

	/**
	 * Database config
	 * 
	 * @var array
	 */
	private static array $db_config = [];

	/**
	 * Stores the current database connection
	 * 
	 * @var array
	 */
	private static array $connection = [];

	private int $transactionLevel = 0;
	
	public function setConnection(string $driver = '') : void
	{
		/** @var array<string|array> */
		static::$db_config = app()->config->database();
		
		if ( ! is_array(static::$db_config) ) {
			throw new \Exception('Database configuration not set.');
		}

		if ( ! isset(static::$db_config['default']) ) {
			die('Database default connection not set');
		}

		if ( ! isset(static::$db_config['connections']) ) {
			die('Database connections not set');
		}

		if ( ! isset(static::$db_config['connections'][static::$db_config['default']]) ) {
			die('Database default connection not set');
		}

		if ( ! isset(static::$db_config['connections'][static::$db_config['default']]['driver']) ) {
			die('Database default connection driver not set');
		}

		if ( ! isset(static::$db_config['connections'][static::$db_config['default']]['host']) ) {
			die('Database default connection host not set');
		}

		if ( ! isset(static::$db_config['connections'][static::$db_config['default']]['port']) ) {
			die('Database default connection port not set');
		}

		if ( ! isset(static::$db_config['connections'][static::$db_config['default']]['database']) ) {
			die('Database default connection database not set');
		}

		if ( ! isset(static::$db_config['connections'][static::$db_config['default']]['username']) ) {
			die('Database default connection username not set');
		}

		if ( ! isset(static::$db_config['connections'][static::$db_config['default']]['password']) ) {
			die('Database default connection password not set');
		}

		if ( ! isset(static::$db_config['connections'][static::$db_config['default']]['charset']) ) {
			die('Database default connection charset not set');
		}

		if ( ! isset(static::$db_config['connections'][static::$db_config['default']]['collation']) ) {
			die('Database default connection collation not set');
		}

		if ( empty($driver) ) {
			static::$connection = static::$db_config['connections'][static::$db_config['default']];
		} else {

			// Verify the connection driver parameter
			if ( ! isset(static::$db_config['connections'][$driver]) ) {
				die('Database connection not set');
			}

			if ( ! isset(static::$db_config['connections'][$driver]['driver']) ) {
				die('Database connection driver not set');
			}

			if ( ! isset(static::$db_config['connections'][$driver]['host']) ) {
				die('Database connection host not set');
			}

			if ( ! isset(static::$db_config['connections'][$driver]['port']) ) {
				die('Database connection port not set');
			}

			if ( ! isset(static::$db_config['connections'][$driver]['database']) ) {
				die('Database connection database not set');
			}

			if ( ! isset(static::$db_config['connections'][$driver]['username']) ) {
				die('Database connection username not set');
			}

			if ( ! isset(static::$db_config['connections'][$driver]['password']) ) {
				die('Database connection password not set');
			}

			if ( ! isset(static::$db_config['connections'][$driver]['charset']) ) {
				die('Database connection charset not set');
			}

			if ( ! isset(static::$db_config['connections'][$driver]['collation']) ) {
				die('Database connection collation not set');
			}

			static::$connection = static::$db_config['connections'][$driver];
		}
	}
	
	public function getPrefix() : string { return static::$prefix ?? env('DB_TABLE_PREFIX'); }
	
	/**
	 * Returns the current database instance.
	 * @return QueryInterface
	 */
	public function getInstance() : QueryInterface
	{
	    if ( ! static::$instance ) {
			self::getPdo();
			self::$instance = new DBQuery;
		}

		return self::$instance;
	}

	/**
	 * Returns the current pdo instance.
	 * @return \PDO
	 */
	public function getPdo() : \PDO
	{
		if ( static::$pdo ) return static::$pdo;

		if ( ! static::$connection || isConsoleMode() ) {
			self::setConnection();
		}
		
		try {
			static::$pdo = new PDO(
				static::$connection['driver'] . ':host=' . static::$connection['host'] . ':' . static::$connection['port'] . ';dbname=' . static::$connection['database'],
				static::$connection['username'],
				static::$connection['password'],
				[
					PDO::ATTR_PERSISTENT => true,
					PDO::ATTR_EMULATE_PREPARES => false
				]
			);

			/**
			 * Set default collation and character set
			 */
			static::$pdo->query('SET NAMES ' . static::$connection['charset']);
			static::$pdo->query('SELECT CONCAT("ALTER TABLE ", tbl.TABLE_SCHEMA, ".", tbl.TABLE_NAME, " CONVERT TO CHARACTER SET ' . static::$connection['charset'] . ' COLLATION ' . static::$connection['collation'] . ';") FROM information_schema.TABLES tbl WHERE tbl.TABLE_SCHEMA = "' . static::$connection['database'] . '"');
			
			static::$prefix = static::$connection['prefix'];

			if ( app()->getTimeTracker() ) {
				static::$pdo->query('SET PROFILING=1');
			}

			return static::$pdo;
		} catch(\PDOException $e) {
			die($e->getMessage());
		}
	}

	/**
	 * Set a PDO instance to be use for the next query.
	 * 
	 * @param \PDO $pdo A PDO object
	 */
	public function setPdo(\PDO $pdo) : void
	{
		static::$pdo = $pdo;
	}
	
	/**
	 * Execute a statement request on the database.
	 * 
	 * @param string $sql The SQL code to execute
	 * @param ?array $options Parameters options
	 * @param ?array $flags Option flags
	 * @return \PDOStatement
	 */
	public function query(string $sql, ?array $options = [], ?array $flags = []) : PDOStatement
	{
		$statement = static::prepare(DBQueryBuilder::bindVars($sql), $flags);
		$statement->execute($options);
		return $statement;
	} 

	/**
	 * Enable query log
	 * Statement generated SQL code will be logged starting from where the call is made.
	 * @return void
	 */
	public function enableQueryLog() : void
	{
		static::$logQuery = true;
	}

	/**
	 * Execute a statement request.
	 * 
	 * @param string $sql
	 * @return int|false
	 */
	public function execute(string $sql) : int|false
	{
		return static::$pdo->exec($sql);
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
		return @ static::query('SELECT FOUND_ROWS()')?->fetch(PDO::FETCH_NUM)[0] ?? 0;
	}

	/**
	 * Prepare a statement
	 * 
	 * @param string $sql
	 * @param ?array $options
	 * @return \PDOStatement
	 */
	public function prepare(string $sql, ?array $options = []) : PDOStatement
	{
		if (!static::$pdo) self::getPdo();
		
		if ( static::$logQuery ) {
			Log::debug($sql);
		}
		
		return self::$pdo->prepare(DBQueryBuilder::bindVars($sql), $options);
	}
	
	/**
	 * Retrieve the error message
	 * 
	 * @return array
	 */
	public function error() : array { return static::$pdo->errorInfo(); }
	
	/**
	 * Retrieve the error code
	 * 
	 * @return string
	 */
	public function errno() : string { return static::$pdo->errorCode(); }
	
	/**
	 * @deprecated
	 */
	public function insertId() : string|false { return static::$pdo->lastInsertId(); }

	/**
	 * Retrieve the last insert id
	 * 
	 * @return string|false
	 */
	public function lastInsertId() : string|false { return static::$pdo->lastInsertId(); }

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
		$pdo = static::$pdo ?? static::getPdo();

		if ( ! isset($callback) ) {
			if ( ! $pdo->inTransaction() ) $pdo->beginTransaction();
			return $pdo;
		}

		if ( is_callable($callback) ) {

			$nested = $pdo->inTransaction();
			$savepoint = 'trans_sp_' . (++$this->transactionLevel);

			try {
				$nested ? $pdo->exec("SAVEPOINT $savepoint") : $pdo->beginTransaction();

				$success = $callback();

				if ( $success ) {
					$nested ? $pdo->exec("RELEASE SAVEPOINT $savepoint") : self::commit();
					$this->transactionLevel--;
					return $success;
				}

				$nested ? $pdo->exec("ROLLBACK TO SAVEPOINT $savepoint") : self::rollback();
				$this->transactionLevel--;
				return $success;

			} catch (\Exception $e) {
				$nested ? $pdo->exec("ROLLBACK TO SAVEPOINT $savepoint") : self::rollback();
				$this->transactionLevel--;
				throw $e;
			}
		}

		return null;
	}

	/**
	 * Begin a transaction
	 * Alias of transaction
	 * 
	 * @param ?\Closure $callback
	 * @return mixed
	 */
	public function beginTransaction(?callable $callback = null) : mixed
	{
		return static::transaction($callback);
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
				return static::transaction($callback);
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
	public function commit() : bool { return !!static::$pdo?->commit(); }

	/**
	 * Rollback back statements
	 * 
	 * @return bool
	 */
	public function rollback() : bool { return !!static::$pdo?->rollback(); }

	/**
	 * Verify if a transaction is already running.
	 * 
	 * @return bool
	 */
	public function inTransaction() : bool { return !!static::$pdo?->inTransaction(); }

	/**
	 * Savepoint statement: Create a savepoint to rollback to.
	 * 
	 * @param string $name The savepoint name
	 * @return \PDOStatement
	 */
	public function savePoint(string $name) : \PDOStatement { return static::statement("SAVEPOINT $name"); }

	/**
	 * Rollback to save point
	 * 
	 * @param string $savePoint 
	 * @see savePoint
	 */
	public function rollbackTo(string $savepoint) : \PDOStatement { return static::statement("ROLLBACK TO SAVEPOINT $savepoint"); }
	
	/**
	 * Prevent transaction anomalies
	 * 
	 * @param int $isolationLevel Specify which isolation level to use
	 * 	Here are the four possible transaction isolation levels
	 * 		- READ UNCOMMITED Allow dirty, nonrepeatable, and phantom reads.
	 * 		- READ COMMITED Allow nonrepeatable and phantom reads.
	 * 		- REPEATABLE READ Allow phantom reads only.
	 * 		- SERIALIZABLE full transaction isolation.
	 * @param ?string $scope Specify the isolation scope: global isolation (GLOBAL) or session isolation (SESSION)
	 * @return \PDOStatement
	 */
	public function isolateTransaction(int $isolationLevel, ?string $scope = '') : \PDOStatement
	{
		$query = "SET TRANSACTION ISOLATION LEVEL $scope";

		return match ($isolationLevel) {
			self::TRANSACTION_DIRTY_READS|self::TRANSACTION_NON_REPEATABLE_READS|self::TRANSACTION_PHANTOM_READS => static::$pdo->query($query . ' READ UNCOMMITED'),
			self::TRANSACTION_NON_REPEATABLE_READS|self::TRANSACTION_PHANTOM_READS => static::$pdo->query($query . ' READ COMMITED'),
			self::TRANSACTION_NON_REPEATABLE_READS => static::$pdo->query($query . ' REPEATABLE READ'),
			default => static::$pdo->query($query . ' SERIALIZABLE')
		};
	}
	
	public function close() : void { static::$pdo = null; }

	public function table(array|string $tables) : QueryInterface
	{
		$builder = new DBQuery;
		$builder->set('query', DBQuery::SELECT);
		
		if ( is_string( $tables ) ) {
			$builder->set('tables', [$tables]);
		} elseif ( is_array($tables) ) {
			$builder->set('tables', $tables);
		}
		
		return $builder;
	}

	public function select(string $sql, ?array $options = [], ?array $flags = []) : array
	{
		$statement = self::statement($sql, $options, $flags);
		return $statement->fetchAll(...$flags);
	}

	public function selectOne(string $sql, ?array $options = [], ?array $flags = []) : array
	{
		$statement = self::statement($sql, $options, $flags);
		return $statement->fetch(...$flags);
	}

	public function statement(string $sql, ?array $options = [], ?array $flags = []) : PDOStatement
	{
		$statement = static::getPdo()->prepare($sql, ...$flags);
		$statement->execute($options);
		return $statement;
	}

	public function unprepared(string $sql) : PDOStatement
	{
		return static::$pdo->query($sql);
	}

	public function connection(string $driver = '') : QueryInterface
	{
		if ( ! empty($driver) ) {
			self::close();
			self::setConnection($driver);
			self::getPdo();
		}

		return static::$instance = new DBQuery;
	}

	public function listen(string $event, callable $callback) : void
	{
		( new \Clicalmani\Database\Events\QueryTimeTracker )->listen($event, $callback);
	}
}
