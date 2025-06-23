<?php
namespace Clicalmani\Database\Interfaces;

use Clicalmani\Database\Interfaces\QueryInterface;

interface DBInterface
{
    /**
	 * Returns a database connection by specifying the driver as argument.
	 * 
	 * @param string $driver Database driver
	 * @return void
	 */
	public function setConnection(string $driver = '') : void;

    /**
	 * Returns the default database table prefix
	 * 
	 * @return string Database table prefix
	 */
	public function getPrefix() : string;

    /**
	 * Returns a single database instance.
	 * 
	 * @return \Clicalmani\Database\Interfaces\QueryInterface object
	 */
	public function getInstance() : QueryInterface;

	/**
	 * Returns PDO instance
	 * 
	 * @return \PDO instance
	 */
	public function getPdo() : \PDO;

	/**
	 * Set PDO instance
	 * 
	 * @param \PDO $pdo
	 * @return void
	 */
	public function setPdo(\PDO $pdo) : void;

	/**
	 * Execute a SQL query
	 * 
	 * @param string $sql SQL statement
	 * @param array $options Statement options
	 * @param array $flags Statement flags
	 * @return \PDO::Statement
	 */
	public function query(string $sql, ?array $options = [], ?array $flags = []) : \PDOStatement;

	/**
	 * Enable query log
	 * 
	 * @return void
	 */
	public function enableQueryLog() : void;

	/**
	 * Execute a SQL query
	 * 
	 * @param string $sql SQL statement
	 * @return int|false
	 */
	public function execute(string $sql) : int|false;

	/**
	 * Fetch a result set by returning an associative array.
	 * 
	 * @param \PDO::Statement $statement
	 * @param int \PDO Constant default is PDO::FETCH_BOTH
	 * @return mixed Result row on success, false on failure.
	 */
	public function fetch(\PDOStatement $statement, int $flag = \PDO::FETCH_BOTH) : mixed;

	/**
	 * Fetch all rows from a result set.
	 * 
	 * @param \PDO::Statement $statement
	 * @param int \PDO Constant default is PDO::FETCH_BOTH
	 * @return mixed Result row on success, false on failure.
	 */
	public function fetchAll(\PDOStatement $statement, int $flag = \PDO::FETCH_BOTH) : mixed;

	/**
	 * Fetch the first row from a result set.
	 * 
	 * @param \PDO::Statement $statement
	 * @param int \PDO Constant default is PDO::FETCH_BOTH
	 * @return mixed Result row on success, false on failure.
	 */
	public function getRow(\PDOStatement $statement, int $flag = \PDO::FETCH_NUM) : mixed;

	/**
	 * Returns the number of rows in the result set.
	 * 
	 * @param \PDO::Stattement $statement
	 * @return int the number of rows, or 0 otherwise.
	 */
	public function numRows(\PDOStatement $statement) : int;

	/**
	 * Returns rows count for CALC_FOUND_ROWS enabled statements.
	 * 
	 * @return int the number of rows, or 0 otherwise.
	 */
	public function foundRows() : int;

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
	public function prepare(string $sql, ?array $options = []) : \PDOStatement;

	/**
	 * Fetch extended error information associated with the last operation on the database handle.
	 * 
	 * @see \PDO::errorInfo() method
	 * @return array An array of error information about the last operation peroformed on the database handle
	 */
	public function error() : array;

	/**
	 * Fetch the SQLSTATE associated with the last operation on the database handle.
	 * 
	 * @return string An SQLSTATE
	 */
	public function errno() : string;

	/**
	 * Returns the ID of the last inserted row or sequence value.
	 * 
	 * @param string [optional] $name name of the sequence object from which the ID should be returned.
	 * @return string|false 
	 */
	public function lastInsertId() : string|false;

	/**
	 * Destroy a statement
	 * 
	 * @param \PDO::Statement $statement the statement to destroy.
	 * @return ?bool null on success or false on failure.
	 */
	public function free(\PDOStatement $statement) : ?bool;

	/**
	 * Begins a database transaction
	 * 
	 * @param ?callable $callback A callback function
	 * @return mixed 
	 * @throws \Exception
	 */
	public function transaction(?callable $callback = null) : mixed;

	/**
	 * Alias of transaction
	 * 
	 * @param callable $callback A callback function
	 * @return mixed 
	 */
	public function beginTransaction(?callable $callback = null) : mixed;

	/**
	 * Handle transaction deadlock
	 * 
	 * @param callable $callback
	 * @param int $attemps Default 5
	 * @param int $sleep Default 100ms
	 * @return mixed
	 */
	public function deadlock(callable $callback, int $attemps = 5, int $sleep = 100) : mixed;

	/**
	 * Validate a transaction
	 * 
	 * @return bool
	 */
	public function commit() : bool;

	/**
	 * Abort a transaction
	 * 
	 * @return bool
	 */
	public function rollback() : bool;

	/**
	 * Check if inside a transaction
	 * 
	 * @return bool
	 */
	public function inTransaction() : bool;

	/**
	 * Create a save point
	 * 
	 * @param string $name Save point name
	 * @return \PDOStatement
	 */
	public function savePoint(string $name) : \PDOStatement;

	/**
	 * Rollback to a specified save point
	 * 
	 * @param string $savepoint Save point name
	 * @return \PDOStatement
	 */
	public function rollbackTo(string $savepoint) : \PDOStatement;

	/**
	 * Isolate a transaction
	 * 
	 * @param int $isolation_lavel
	 * @param ?string $scope
	 * @return \PDOStatement
	 */
	public function isolateTransaction(int $isolation_lavel, string $scope = '') : \PDOStatement;

	/**
	 * Destroy the database connection
	 * 
	 * @return void
	 */
	public function close() : void;

	/**
	 * Select a database table on which to execute a SQL query.
	 * 
	 * @param array|string $tables Database table(s) name(s)
	 * @return \Clicalmani\Database\Interfaces\QueryInterface Object
	 */
	public function table(array|string $tables) : QueryInterface;

	/**
	 * Select raw SQL query
	 * 
	 * @param string $sql SQL statement
	 * @param array $options Statement options
	 * @param array $flags Statement flags
	 * @return array
	 */
	public function select(string $sql, array $options = [], array $flags = []) : array;

	/**
	 * Select one raw SQL query
	 * 
	 * @param string $sql SQL statement
	 * @param array $options Statement options
	 * @param array $flags Statement flags
	 * @return array
	 */
	public function selectOne(string $sql, array $options = [], array $flags = []) : array;

	/**
	 * Execute a raw SQL query
	 * 
	 * @param string $sql SQL statement
	 * @param array $options Statement options
	 * @param array $flags Statement flags
	 * @return \PDOStatement
	 */
	public function statement(string $sql, array $options = [], array $flags = []) : \PDOStatement;

	/**
	 * Execute a raw SQL query
	 * 
	 * @param string $sql SQL statement
	 * @return \PDOStatement
	 */
	public function unprepared(string $sql) : \PDOStatement;

	/**
	 * Establish a database connection
	 * 
	 * @param string $driver Database driver
	 * @return \Clicalmani\Database\Interfaces\QueryInterface Object
	 */
	public function connection(string $driver = '') : QueryInterface;

	/**
	 * Listen for database query cumulative time.
	 * 
	 * @param string $event Event name
	 * @param callable $callback Callback function
	 * @return void
	 */
	public function listen(string $event, callable $callback) : void;
}