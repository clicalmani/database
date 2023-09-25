<?php
namespace Clicalmani\Flesco\Database;

/**
 * |--------------------------------------------------------------------------------
 * |                            ***** DB Class *****
 * |--------------------------------------------------------------------------------
 * 
 * Database abstraction class
 * 
 * DB class use PHP Data Objects (PDO) extension interface for accessing database.
 * It uses MySQL database driver as its default driver. Other databases can be used 
 * by specifing the corresponding specific PDO driver.
 * 
 * @package Flesco\Database
 * @author Abdoul-Madjid
 * @version 3.0.1
 * @since 2015
 */

use PDO;
use \PDOStatement;

global $db_config, $root_path;

/**
 * Default database configuarion
 */
$db_config = require config_path( '/database.php' );

abstract class DB 
{
	/**
	 * Stores the single database instance for all connections.
	 */
	static private $instance;

	/**
	 * Stores PDO instance
	 */
	static private $pdo;

	/**
	 * Database tables prefix
	 */
	static private $prefix;
	
	/**
	 * Stores different database connections
	 */
	private $cons = [];
	
	/**
	 * Returns a database connection by specifying the driver as argument.
	 * 
	 * @param string $driver Database driver
	 * @return \PDO Object
	 */
	public function getConnection($driver = '') 
	{ 
		if (empty($driver)) {
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
	 * @return \Clicalmani\Flesco\Database\DB object
	 */
	static function getInstance() 
	{
	    if ( ! static::$instance ) {
			self::getPdo();
			return new DBQuery;
		}

		return static::$instance;
	}

	/**
	 * Returns PDO instance
	 * 
	 * @return \PDO instance
	 */
	static function getPdo() {
		if ( isset(static::$pdo) ) return static::$pdo;

		global $db_config;

		try {
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

			static::$pdo->query('SET NAMES ' . $db_default['charset']);
			static::$pdo->query('SELECT CONCAT("ALTER TABLE ", tbl.TABLE_SCHEMA, ".", tbl.TABLE_NAME, " CONVERT TO CHARACTER SET ' . $db_default['charset'] . ' COLLATION ' . $db_default['collation'] . ';") FROM information_schema.TABLES tbl WHERE tbl.TABLE_SCHEMA = "' . $db_default['name'] . '"');
			
			static::$prefix = $db_default['prefix'];

			return static::$pdo;
		} catch(\PDOException $e) {
			die('An error occurred while trying to connect to the database server, please contact your administrator for further informations.');
		}
	}
	
	/**
	 * Execute a database query
	 * 
	 * @param string $sql SQL command structure
	 * @return \PDO::Statement
	 */
	public function query($sql, $options = [], $flags = []) 
	{ 
		$statement = $this->prepare($sql, $flags);
		$statement->execute($options);

		return $statement;
	} 

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
	public function fetch($statement, $flag = PDO::FETCH_BOTH) 
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
	public function getRow($statement, $flag = PDO::FETCH_NUM) 
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
	public function numRows($statement) 
	{ 
	    
		if ($statement instanceof PDOStatement) return $statement->rowCount(); 
		
		return 0;
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
	public function prepare($sql, $options = [])
	{
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
	public function insertId(?string $name = null) { return static::$pdo->lastInsertId(); }
	
	/**
	 * Destroy a statement
	 * 
	 * @param \PDO::Statement $statement the statement to destroy.
	 * @return bool|null null on success or false on failure.
	 */
	public function free($statement) 
	{ 
	    
		if ($statement instanceof PDOStatement) return $statement = null; 
		
		return false;
	}
	
	public function beginTransaction(\Closure $callable = null) 
	{ 
		if ( !isset($callable) ) return static::$pdo->beginTransaction(); 

		if ( $callable instanceof \Closure ) {
			static::$pdo->beginTransaction();
			$success = $callable();
			if ( $success ) {
				$this->commit();
				return true;
			} else {
				$this->rollback();
				return false;
			}
		}
	}

	/**
	 * Validate a transaction
	 */
	public function commit() { return static::$pdo->commit(); }

	/**
	 * Abort a transaction
	 */
	public function rollback() { return static::$pdo->rollback(); }
	
	/**
	 * Destroy the database connection
	 */
	public function close() { return static::$pdo = null; }

	/**
	 * Select a database table on which to execute a SQL query.
	 * 
	 * @param array|string $tables Database table(s) name(s)
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	static function table($tables)
	{
		$builder = new DBQuery;
		$builder->set('query', DB_QUERY_SELECT);
		
		if ( is_string( $tables ) ) {
			$builder->set('tables', [$tables]);
		} elseif ( is_array($tables) ) {
			$builder->set('tables', $tables);
		}
		
		return $builder;
	}
}
