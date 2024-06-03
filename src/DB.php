<?php
namespace Clicalmani\Database;

use Clicalmani\Fundation\Support\Facades\Log;
use PDO;
use \PDOStatement;

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
	/**
	 * Stores the single database instance for all connections.
	 * 
	 * @var static
	 */
	static private $instance;

	/**
	 * Stores PDO instance
	 * 
	 * @var \PDO
	 */
	static private $pdo;

	/**
	 * Database tables prefix
	 * 
	 * @var string
	 */
	static private $prefix;

	/**
	 * Toggle query log
	 * 
	 * @var bool
	 */
	static private $logQuery = false;
	
	/**
	 * Stores database connections
	 * 
	 * @var ?array
	 */
	private $cons = [];
	
	/**
	 * Returns a database connection by specifying the driver as argument.
	 * 
	 * @param ?string $driver Database driver
	 * @return \PDO Object
	 */
	public function getConnection(?string $driver = '') 
	{ 
		if ($driver === '') {
			return static::$pdo ? static::$pdo : null;
		} 

		/**
		 * Driver provided
		 */
	}
	
	/**
	 * Returns the default database table prefix
	 * 
	 * @return string Database table prefix
	 */
	public function getPrefix() { return static::$prefix; }
	
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
	public static function getPdo() {
		if ( isset(static::$pdo) ) return static::$pdo;
		
		/** @var array<string|array> */
		$db_config = require_once config_path( '/database.php' );
		
		try {

			/** @var array<string> */
			$db_default = $db_config['connections'][$db_config['default']];
			
			static::$pdo = new PDO(
				$db_default['driver'] . ':host=' . $db_default['host'] . ':' . $db_default['port'] . ';dbname=' . $db_default['name'],
				$db_default['user'],
				$db_default['pswd'],
				[
					PDO::ATTR_PERSISTENT => true,
					PDO::ATTR_EMULATE_PREPARES => false
				]
			);

			/**
			 * Set default collation and character set
			 */
			static::$pdo->query('SET NAMES ' . $db_default['charset']);
			static::$pdo->query('SELECT CONCAT("ALTER TABLE ", tbl.TABLE_SCHEMA, ".", tbl.TABLE_NAME, " CONVERT TO CHARACTER SET ' . $db_default['charset'] . ' COLLATION ' . $db_default['collation'] . ';") FROM information_schema.TABLES tbl WHERE tbl.TABLE_SCHEMA = "' . $db_default['name'] . '"');
			
			static::$prefix = $db_default['prefix'];

			return static::$pdo;
		} catch(\PDOException $e) {
			die($e->getMessage());
		}
	}
	
	/**
	 * Execute a database query
	 * 
	 * @param string $sql SQL command structure
	 * @return \PDO::Statement
	 */
	public function query(string $sql, ?array $options = [], ?array $flags = []) 
	{
		$statement = $this->prepare($sql, $flags);
		$statement->execute($options);
		
		return $statement;
	} 

	/**
	 * Enable SQL log
	 * 
	 * @return void
	 */
	public function enableQueryLog() : void
	{
		static::$logQuery = true;
	}

	/**
	 * Execute a SQL query
	 * 
	 * @param string $sql SQL statement
	 * @return int|false
	 */
	public function execute(string $sql) : int|false
	{
		return static::$pdo->exec($sql);
	}
	
	/**
	 * Fetch a result set by returning an associative array
	 * 
	 * @param \PDO::Statement $statement
	 * @param int \PDO Constant default is PDO::FETCH_BOTH
	 * @return mixed Result row on success, false on failure.
	 */
	public function fetch($statement, int $flag = PDO::FETCH_BOTH) : mixed
	{ 
		if ($statement instanceof PDOStatement) return $statement->fetch($flag);
		return null;
	}
	
	/**
	 * Fetch a result set by returning a numeric indexed array.
	 * @param \PDO::Statement $statement
	 * @param int \PDO Constant default is PDO::FETCH_BOTH
	 * @return mixed Result row on success, false on failure.
	 */
	public function getRow($statement, ?int $flag = PDO::FETCH_NUM) : mixed
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
	public function numRows(PDOStatement $statement) : int
	{ 
		if ($statement instanceof PDOStatement) return $statement->rowCount(); 
		return 0;
	}

	/**
	 * Returns rows count for CALC_FOUND_ROWS enabled statements.
	 * 
	 * @return int the number of rows, or 0 otherwise.
	 */
	public function foundRows() : int
	{
		return @ $this->query('SELECT FOUND_ROWS()')?->fetch(PDO::FETCH_NUM)[0] ?? 0;
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
	public function prepare(string $sql, ?array $options = [])
	{
		if ( static::$logQuery ) {
			Log::debug($sql);
		}
		
		return static::$pdo->prepare($sql, $options);
	}
	
	/**
	 * Fetch extended error information associated with the last operation on the database handle.
	 * 
	 * @see \PDO::errorInfo() method
	 * @return array An array of error information about the last operation peroformed on the database handle
	 */
	public function error() { return static::$pdo->errorInfo(); }
	
	/**
	 * Fetch the SQLSTATE associated with the last operation on the database handle.
	 * 
	 * @return string An SQLSTATE
	 */
	public function errno() { return static::$pdo->errorCode(); }
	
	/**
	 * Returns the ID of the last inserted row or sequence value.
	 * 
	 * @param string [optional] $name name of the sequence object from which the ID should be returned.
	 * @return string|false 
	 */
	public function insertId() : string|false { return static::$pdo->lastInsertId(); }
	
	/**
	 * Destroy a statement
	 * 
	 * @param \PDO::Statement $statement the statement to destroy.
	 * @return bool|null null on success or false on failure.
	 */
	public function free(PDOStatement $statement) : bool|null
	{ 
		if ($statement instanceof PDOStatement) return $statement = null; 
		return false;
	}
	
	/**
	 * Begins a database transaction
	 * 
	 * @param callable $callback A callback function
	 * @return mixed 
	 */
	public function beginTransaction(?callable $callback = null) : mixed
	{
		if ( !isset($callback) ) {
			static::$pdo->beginTransaction(); 
			return static::$pdo;
		}

		if ( is_callable($callback) ) {
			static::$pdo->beginTransaction();
			$success = $callback();
			if ( $success ) {
				$this->commit();
				return $success;
			} else {
				$this->rollback();
				return $success;
			}
		}
	}

	/**
	 * Validate a transaction
	 * 
	 * @return bool
	 */
	public function commit() : bool { return static::$pdo->commit(); }

	/**
	 * Abort a transaction
	 * 
	 * @return bool
	 */
	public function rollback() : bool { return static::$pdo->rollback(); }
	
	/**
	 * Destroy the database connection
	 * 
	 * @return void
	 */
	public function close() : void { static::$pdo = null; }

	/**
	 * Select a database table on which to execute a SQL query.
	 * 
	 * @param array|string $tables Database table(s) name(s)
	 * @return \Clicalmani\Database\DBQuery Object
	 */
	static function table(array|string $tables) : DBQuery
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
}
