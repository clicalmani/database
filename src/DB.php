<?php
namespace Clicalmani\Database;

use Clicalmani\Database\Interfaces\QueryInterface;
use Clicalmani\Foundation\Support\Facades\Log;
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
abstract class DB implements Interfaces\DBInterface
{
	const TRANSACTION_DIRTY_READS = 0x0;

	const TRANSACTION_NON_REPEATABLE_READS = 0x0;

	const TRANSACTION_PHANTOM_READS = 0x1;

	/**
	 * Stores the single database instance for all connections.
	 * 
	 * @var \Clicalmani\Database\Interfaces\QueryInterface
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
	private static $prefix;

	/**
	 * Toggle query log
	 * 
	 * @var bool
	 */
	private static $logQuery = false;

	/**
	 * DB config
	 * 
	 * @var array
	 */
	private static $db_config;

	/**
	 * Database connection
	 * 
	 * @var array
	 */
	private static $connection;
	
	public function setConnection(string $driver = '') : void
	{
		/** @var array<string|array> */
		static::$db_config = app()->config->database();
		
		if ( is_array(static::$db_config) ) {

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
	}
	
	public function getPrefix() : string { return static::$prefix ?? env('DB_TABLE_PREFIX'); }
	
	public function getInstance() : \Clicalmani\Database\Interfaces\QueryInterface
	{
	    if ( ! static::$instance ) {
			self::getPdo();
			self::$instance = new DBQuery;
		}

		return self::$instance;
	}

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

	public function setPdo(\PDO $pdo) : void
	{
		static::$pdo = $pdo;
	}
	
	public function query(string $sql, ?array $options = [], ?array $flags = []) : PDOStatement
	{
		$statement = static::prepare(DBQueryBuilder::bindVars($sql), $flags);
		$statement->execute($options);
		
		return $statement;
	} 

	public function enableQueryLog() : void
	{
		static::$logQuery = true;
	}

	public function execute(string $sql) : int|false
	{
		return static::$pdo->exec($sql);
	}

	public function fetch($statement, int $flag = PDO::FETCH_BOTH) : mixed
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
	
	public function getRow($statement, ?int $flag = PDO::FETCH_NUM) : mixed
	{
		if ($statement instanceof PDOStatement) return $statement->fetch($flag);
		return [];
	}
	
	public function numRows(PDOStatement $statement) : int
	{ 
		if ($statement instanceof PDOStatement) return $statement->rowCount(); 
		return 0;
	}

	public function foundRows() : int
	{
		return @ static::query('SELECT FOUND_ROWS()')?->fetch(PDO::FETCH_NUM)[0] ?? 0;
	}

	public function prepare(string $sql, ?array $options = []) : PDOStatement
	{
		if (!static::$pdo) self::getPdo();
		
		if ( static::$logQuery ) {
			Log::debug($sql);
		}
		
		return self::$pdo->prepare(DBQueryBuilder::bindVars($sql), $options);
	}
	
	public function error() : array { return static::$pdo->errorInfo(); }
	
	public function errno() : string { return static::$pdo->errorCode(); }
	
	/**
	 * @deprecated
	 */
	public function insertId() : string|false { return static::$pdo->lastInsertId(); }

	public function lastInsertId() : string|false { return static::$pdo->lastInsertId(); }

	public function free(PDOStatement $statement) : ?bool
	{ 
		if ($statement instanceof PDOStatement) return $statement = null; 
		return false;
	}

	public function transaction(?callable $callback = null) : mixed
	{
		if ( !isset($callback) ) {
			static::$pdo->beginTransaction(); 
			return static::$pdo;
		}

		if ( is_callable($callback) ) {
			
			static::$pdo->beginTransaction();
			
			try {

				$success = $callback();
				
				if ( $success ) {
					static::commit();
					return $success;
				}

				static::rollback();

				return $success;
			} catch (\Exception $e) {
				static::rollback();
				throw new \Exception($e->getMessage(), 0, $e);
			}
		}

		return null;
	}

	public function beginTransaction(?callable $callback = null) : mixed
	{
		return static::transaction($callback);
	}

	public function deadlock(callable $callback, int $attemps = 5, int $sleep = 100) : mixed
	{
		while ($attemps--) {
			try {
				return static::transaction($callback);
			} catch (\PDOException $e) {
				if ($attemps === 0) {
					throw $e;
				}
				usleep($sleep);
			}
		}

		return null;
	}

	public function commit() : bool { return !!static::$pdo?->commit(); }

	public function rollback() : bool { return !!static::$pdo?->rollback(); }

	public function inTransaction() : bool { return !!static::$pdo?->inTransaction(); }

	public function savePoint(string $name) : \PDOStatement { return static::statement("SAVEPOINT $name"); }

	public function rollbackTo(string $savepoint) : \PDOStatement { return static::statement("ROLLBACK TO SAVEPOINT $savepoint"); }
	
	public function isolateTransaction(int $isolation_lavel, ?string $scope = '') : \PDOStatement
	{
		$query = "SET TRANSACTION ISOLATION LEVEL $scope";

		return match ($isolation_lavel) {
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
		$statement = static::$pdo->prepare($sql);
		$statement->execute($options);
		
		return $statement->fetchAll(...$flags);
	}

	public function selectOne(string $sql, ?array $options = [], ?array $flags = []) : array
	{
		$statement = static::$pdo->prepare($sql);
		$statement->execute($options);
		
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
