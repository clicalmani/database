<?php
namespace Clicalmani\Database;

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
abstract class DB 
{
	const TRANSACTION_DIRTY_READS = 0x0;

	const TRANSACTION_NON_REPEATABLE_READS = 0x0;

	const TRANSACTION_PHANTOM_READS = 0x1;

	/**
	 * Stores the single database instance for all connections.
	 * 
	 * @var static
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
	
	/**
	 * Returns a database connection by specifying the driver as argument.
	 * 
	 * @param ?string $driver Database driver
	 * @return void
	 */
	public static function setConnection(?string $driver = '') : void
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
	
	/**
	 * Returns the default database table prefix
	 * oui
	 * @return string Database table prefix
	 */
	public static function getPrefix() { return static::$prefix; }
	
	/**
	 * Returns a single database instance.
	 * 
	 * @return \Clicalmani\Database\DBQuery object
	 */
	public static function getInstance() : DBQuery
	{
	    if ( ! static::$instance ) {
			self::getPdo();
			static::$instance = new DBQuery;
		}

		return static::$instance;
	}

	/**
	 * Returns PDO instance
	 * 
	 * @return \PDO instance
	 */
	public static function getPdo() 
	{
		if ( static::$pdo ) return static::$pdo;

		if ( ! static::$connection || inConsoleMode() ) {
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

			if ( app()->config->database('listeners') ) {
				static::$pdo->query('SET PROFILING=1');
			}

			return static::$pdo;
		} catch(\PDOException $e) {
			die($e->getMessage());
		}
	}

	/**
	 * Set PDO instance
	 * 
	 * @param \PDO $pdo
	 * @return void
	 */
	public static function setPdo(\PDO $pdo) : void
	{
		static::$pdo = $pdo;
	}
	
	/**
	 * Execute a SQL query
	 * 
	 * @param string $sql SQL statement
	 * @param array $options Statement options
	 * @param array $flags Statement flags
	 * @return \PDO::Statement
	 */
	public static function query(string $sql, ?array $options = [], ?array $flags = []) : PDOStatement
	{
		$statement = static::prepare(DBQueryBuilder::bindVars($sql), $flags);
		$statement->execute($options);
		
		return $statement;
	} 

	/**
	 * Enable query log
	 * 
	 * @return void
	 */
	public static function enableQueryLog() : void
	{
		static::$logQuery = true;
	}

	/**
	 * Execute a SQL query
	 * 
	 * @param string $sql SQL statement
	 * @return int|false
	 */
	public static function execute(string $sql) : int|false
	{
		return static::$pdo->exec($sql);
	}
	
	/**
	 * Fetch a result set by returning an associative array.
	 * 
	 * @param \PDO::Statement $statement
	 * @param int \PDO Constant default is PDO::FETCH_BOTH
	 * @return mixed Result row on success, false on failure.
	 */
	public static function fetch($statement, int $flag = PDO::FETCH_BOTH) : mixed
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
	public static function fetchAll($statement, int $flag = PDO::FETCH_BOTH) : mixed
	{
		if ($statement instanceof PDOStatement) return $statement->fetchAll($flag);
		return [];
	}
	
	/**
	 * Fetch the first row from a result set.
	 * 
	 * @param \PDO::Statement $statement
	 * @param int \PDO Constant default is PDO::FETCH_BOTH
	 * @return mixed Result row on success, false on failure.
	 */
	public static function getRow($statement, ?int $flag = PDO::FETCH_NUM) : mixed
	{
		if ($statement instanceof PDOStatement) return $statement->fetch($flag);
		return [];
	}
	
	/**
	 * Returns the number of rows in the result set.
	 * 
	 * @param \PDO::Stattement $statement
	 * @return int the number of rows, or 0 otherwise.
	 */
	public static function numRows(PDOStatement $statement) : int
	{ 
		if ($statement instanceof PDOStatement) return $statement->rowCount(); 
		return 0;
	}

	/**
	 * Returns rows count for CALC_FOUND_ROWS enabled statements.
	 * 
	 * @return int the number of rows, or 0 otherwise.
	 */
	public static function foundRows() : int
	{
		return @ static::query('SELECT FOUND_ROWS()')?->fetch(PDO::FETCH_NUM)[0] ?? 0;
	}

	/**
	 * Prepare an SQL statement to be executed. The statement template can contain zero o more named (:name)
	 * or question mark parameters (?) markers for which real values will be subtituted when the statement is executed.
	 * Both named and question mark parameters can not been used within the same statement template.
	 * 
	 * @param string $sql a SQL statement structure
	 * @param array Statement parameters values
	 * 
	 * @see \PDO::prepare() method
	 * @return \PDO::Statement
	 */
	public static function prepare(string $sql, ?array $options = [])
	{
		if ( static::$logQuery ) {
			Log::debug($sql);
		}
		
		return static::$pdo->prepare(DBQueryBuilder::bindVars($sql), $options);
	}
	
	/**
	 * Fetch extended error information associated with the last operation on the database handle.
	 * 
	 * @see \PDO::errorInfo() method
	 * @return array An array of error information about the last operation peroformed on the database handle
	 */
	public static function error() { return static::$pdo->errorInfo(); }
	
	/**
	 * Fetch the SQLSTATE associated with the last operation on the database handle.
	 * 
	 * @return string An SQLSTATE
	 */
	public static function errno() { return static::$pdo->errorCode(); }
	
	/**
	 * Returns the ID of the last inserted row or sequence value.
	 * 
	 * @param string [optional] $name name of the sequence object from which the ID should be returned.
	 * @return string|false 
	 */
	public static function insertId() : string|false { return static::$pdo->lastInsertId(); }
	
	/**
	 * Destroy a statement
	 * 
	 * @param \PDO::Statement $statement the statement to destroy.
	 * @return bool|null null on success or false on failure.
	 */
	public static function free(PDOStatement $statement) : bool|null
	{ 
		if ($statement instanceof PDOStatement) return $statement = null; 
		return false;
	}

	/**
	 * Begins a database transaction
	 * 
	 * @param ?callable $callback A callback function
	 * @return mixed 
	 */
	public static function transaction(?callable $callback = null) : mixed
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
				return false;
			}
		}
	}
	
	/**
	 * Begins a database transaction
	 * 
	 * @param callable $callback A callback function
	 * @return mixed 
	 */
	public static function beginTransaction(?callable $callback = null) : mixed
	{
		return static::transaction($callback);
	}

	/**
	 * Handle transaction deadlock
	 * 
	 * @param callable $callback
	 * @param ?int $attemps Default 5
	 * @param ?int $sleep Default 100ms
	 * @return mixed
	 */
	public static function deadlock(callable $callback, ?int $attemps = 5, ?int $sleep = 100) : mixed
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
	}

	/**
	 * Validate a transaction
	 * 
	 * @return bool
	 */
	public static function commit() : bool { return static::$pdo->commit(); }

	/**
	 * Abort a transaction
	 * 
	 * @return bool
	 */
	public static function rollback() : bool { return static::$pdo->rollback(); }

	/**
	 * Check if inside a transaction
	 * 
	 * @return bool
	 */
	public static function inTransaction() : bool { return static::$pdo->inTransaction(); }

	/**
	 * Create a save point
	 * 
	 * @param string $name Save point name
	 * @return \PDOStatement
	 */
	public static function savePoint(string $name) : \PDOStatement { return static::statement("SAVEPOINT $name"); }

	/**
	 * Rollback to a specified save point
	 * 
	 * @param string $savepoint Save point name
	 * @return \PDOStatement
	 */
	public static function rollbackTo(string $savepoint) { return static::statement("ROLLBACK TO SAVEPOINT $savepoint"); }
	
	/**
	 * Isolate a transaction
	 * 
	 * @param int $isolation_lavel
	 * @param ?string $scope
	 * @return \PDOStatement
	 */
	public static function isolateTransaction(int $isolation_lavel, ?string $scope = '')
	{
		$query = "SET TRANSACTION ISOLATION LEVEL $scope";

		return match ($isolation_lavel) {
			self::TRANSACTION_DIRTY_READS|self::TRANSACTION_NON_REPEATABLE_READS|self::TRANSACTION_PHANTOM_READS => static::$pdo->query($query . ' READ UNCOMMITED'),
			self::TRANSACTION_NON_REPEATABLE_READS|self::TRANSACTION_PHANTOM_READS => static::$pdo->query($query . ' READ COMMITED'),
			self::TRANSACTION_NON_REPEATABLE_READS => static::$pdo->query($query . ' REPEATABLE READ'),
			default => static::$pdo->query($query . ' SERIALIZABLE')
		};
	}
	
	/**
	 * Destroy the database connection
	 * 
	 * @return void
	 */
	public static function close() : void { static::$pdo = null; }

	/**
	 * Select a database table on which to execute a SQL query.
	 * 
	 * @param array|string $tables Database table(s) name(s)
	 * @return \Clicalmani\Database\DBQuery Object
	 */
	public static function table(array|string $tables) : DBQuery
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

	/**
	 * Select raw SQL query
	 * 
	 * @param string $sql SQL statement
	 * @param array $options Statement options
	 * @param array $flags Statement flags
	 * @return array
	 */
	public static function select(string $sql, ?array $options = [], ?array $flags = []) : array
	{
		$statement = static::$pdo->prepare($sql);
		$statement->execute($options);
		
		return $statement->fetchAll(...$flags);
	}

	/**
	 * Select one raw SQL query
	 * 
	 * @param string $sql SQL statement
	 * @param array $options Statement options
	 * @param array $flags Statement flags
	 * @return array
	 */
	public static function selectOne(string $sql, ?array $options = [], ?array $flags = []) : array
	{
		$statement = static::$pdo->prepare($sql);
		$statement->execute($options);
		
		return $statement->fetch(...$flags);
	}

	/**
	 * Execute a raw SQL query
	 * 
	 * @param string $sql SQL statement
	 * @param array $options Statement options
	 * @param array $flags Statement flags
	 * @return \PDOStatement
	 */
	public static function statement(string $sql, ?array $options = [], ?array $flags = []) : PDOStatement
	{
		$statement = static::getPdo()->prepare($sql, ...$flags);
		$statement->execute($options);
		
		return $statement;
	}

	/**
	 * Execute a raw SQL query
	 * 
	 * @param string $sql SQL statement
	 * @return \PDOStatement
	 */
	public static function unprepared(string $sql) : PDOStatement
	{
		return static::$pdo->query($sql);
	}

	/**
	 * Establish a database connection
	 * 
	 * @param string $driver Database driver
	 * @return \Clicalmani\Database\DBQuery Object
	 */
	public static function connection(string $driver = '') : DBQuery
	{
		if ( ! empty($driver) ) {
			self::close();
			self::setConnection($driver);
			self::getPdo();
		}

		return static::$instance = new DBQuery;
	}

	/**
	 * Listen for database query cumulative time.
	 * 
	 * @param string $event Event name
	 * @param callable $callback Callback function
	 * @return void
	 */
	public static function listen(string $event, callable $callback) : void
	{
		( new \Clicalmani\Database\Events\QueryTimeTracker )->listen($event, $callback);
	}
}
