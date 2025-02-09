<?php
namespace Clicalmani\Database;

use Clicalmani\Foundation\Collection\Collection;

/**
 * Class Database Query Builder
 * 
 * Builds a SQL query to be executed in a database statement.
 * 
 * @package Clicaomani\Database
 * @author @clicalmani
 */
abstract class DBQueryBuilder 
{
	/**
	 * Holds the generated SQL statement to be executed.
	 * 
	 * @var string 
	 */
	protected $sql;

	/**
	 * DBQuery object
	 * 
	 * @var \Cicalmani\Database\DBQuery
	 */
	protected $db;

	/**
	 * Pagination range
	 * 
	 * @var int Default 5
	 */
	protected $range;

	/**
	 * Number of rows to be returns while executing the current SQL statement.
	 * 
	 * @var int Default 25
	 */
	protected $limit;

	/**
	 * SQL error message
	 * 
	 * @var string 
	 */
	protected $error_msg;

	/**
	 * SQL error code
	 * 
	 * @var int
	 */
	protected $error_code;

	/**
	 * Last insert ID
	 * 
	 * @var int
	 */
	protected $insert_id = 0;

	/**
	 * Number of rows returned while executing the current SQL statement.
	 * 
	 * @var int Default 0
	 */
	protected $num_rows = 0;

	/**
	 * profile
	 * 
	 * @var array
	 */
	protected $profile;

	/**
	 * Human understandable status result
	 * 
	 * @var string Possible values are success or failure
	 */
	protected $status;

	/**
	 * SQL result
	 * 
	 * @var \Clicalmani\Foundation\Collection\Collection
	 */
	protected $result; 

	/**
	 * Iterator index
	 * 
	 * @var int Default 0
	 */
	protected $key = 0;

	/**
	 * Different types of tables joins supported
	 * 
	 * @var array
	 */
	protected const JOIN_TYPES = [
		'left'  => 'LEFT JOIN',
		'right' => 'RIGHT JOIN',
		'inner' => 'INNER JOIN',
		'cross' => 'CROSS JOIN'
	];

	/**
	 * Cumulative time listeners
	 * 
	 * @var array
	 */
	protected $cumulative_time_listeners = [];

	/**
	 * Constructor
	 * 
	 * @param array $param [Optional]
	 * @param array $option [Optional]
	 */
	public function __construct(
		protected $params = [],
		protected $options = []
	)
	{
		$this->params = $params; 
		
		$default = array(
			'offset'    => 0, 
			'limit'     => null,
			'num_rows'  => 25,
			'query_str' => [],
			'options'   => []                                        
		);
		
		foreach ($default as $key => $option){
			if (!isset($this->params[$key])) $this->params[$key] = $default[$key];
		}
		
		$this->db     = DB::getInstance(); 
		$this->result = new Collection;
		$this->cumulative_time_listeners = app()->config->database('listeners');
	}
	
	/**
	 * Execute a SQL statement
	 * 
	 * @return void
	 */
	public abstract function query() : void;
	
	/**
	 * Execute a SQL statement
	 * 
	 * @param string $sql
	 */
	public function execSQL(string $sql) : int|false
	{
		return $this->db->execute($this->bindVars($sql));
	}
	
	/**
	 * Bind vars
	 * 
	 * @return string
	 */
	public static function bindVars(string $query) : string
	{
		$bindings = array(
			'%PREFIX%'=> DB::getPrefix()
		);
		
		foreach ($bindings as $key => $value) {
			$query = str_replace($key, $value, $query);
		}

		return $query;
	}
	
	/**
	 * Fetch a single row from the SQL query result.
	 * 
	 * @return array
	 */
	public function getRow() : array { return $this->result->get($this->key); }
	
	/**
	 * Check wether the last SQL statement has a result
	 * 
	 * @return bool True on success, false on failure
	 */
	public function hasResult() : bool { return $this->num_rows > 0; }
	
	/**
	 * Gets the query result set.
	 * 
	 * @return \Clicalmani\Foundation\Collection\Collection
	 */
	public function result() : Collection { return $this->result; }
	
	/**
	 * Returns the number of rows returned by the last SQL statement.
	 * 
	 * @return int
	 */
	public function numRows() : int { return $this->num_rows; }
	
	/**
	 * Returns human understandable word to show wether the execution of the
	 * SQL statement has succed or not.
	 * 
	 * @return string
	 */
	public function status() : string { return $this->status ? 'success': 'failure'; }
	
	/**
	 * Last insert ID
	 * 
	 * @return int
	 */
	public function insertId() : int { return $this->insert_id; }
	
	/**
	 * Returns the iterator key
	 * 
	 * @return int
	 */
	public function key() : int { return $this->key; }
	
	/**
	 * Sets the iterator key
	 * 
	 * @param int $new_key
	 * @return void
	 */
	public function setKey(int $new_key) : void { $this->key = $new_key; }
	
	/**
	 * Close the database connection
	 * 
	 * @return void
	 */
	public function close() : void { $this->db->close(); }
	
	/**
	 * Returns the generated SQL 
	 * 
	 * @return string 
	 */
	public function getSQL() : string { 
		return self::bindVars($this->sql); 
	}
	
	/**
	 * Sanitize tables
	 * 
	 * @param array $tables
	 * @param bool $prefix
	 * @param bool $alias
	 * @return array
	 */
	public function sanitizeTables(array $tables, bool $prefix = true, bool $alias = true) : array
	{
		$ret = [];

		for ($i=0; $i<sizeof($tables); $i++) {
			
			$arr = preg_split('/\s/', $tables[$i], -1, PREG_SPLIT_NO_EMPTY);
			$alias = end($arr);
			
			$table = $arr[0];
			$alias = $alias !== $table ? $alias: null;

			if (true == $prefix) $table = $this->db->getPrefix() . $table;
			if (true == $alias AND $alias) $table = $table . ' ' . $alias;
			
			$ret[] = $table;
		}

		return $ret;
	}

	/**
	 * Adds joint in the SQL statement
	 * 
	 * @param array $joint
	 * @return string
	 */
	public function addJoint(array $joint) : string
	{
		$ret = '';

		if ( isset($joint['type']) ) {
			$ret .= ' ' . self::JOIN_TYPES[strtolower($joint['type'])];
		}

		if ( isset($joint['table']) ) {
			$ret .= ' ' . join(',', $this->sanitizeTables([$joint['table']]));
		}

		if ( isset($joint['sub_query']) ) {
			$ret .= ' (' . $joint['sub_query'] . ')';

			if ( isset($joint['alias']) ) {
				$ret .= ' ' . $joint['alias'];
			}
		}

		if ( isset($joint['criteria']) ) {
			$ret .= ' ' . $joint['criteria'];
		}

		return $ret;
	}

	/**
	 * Sanitize a value
	 * 
	 * @param string $value
	 * @return string
	 */
	public function sanitizeValue(string $value) : string
	{
		if (is_bool($value)) {
			return (int) $value;
		}
		
		return $value;
	}

	/**
	 * Gets the corresponding PDO data type from the supplied data.
	 * 
	 * @param mixed $data
	 * @return int
	 */
	public function getDataType(mixed $data) : int
	{
		$value = $data;

		if ( is_subclass_of($data, \Clicalmani\Database\Factory\DataTypes\DataType::class) ) {
			$value = $data->getValue();
			settype($value, $data->getType());
		}
		
		if ( is_int($value) ) return \PDO::PARAM_INT;
		if ( is_bool($value) ) return \PDO::PARAM_BOOL;
		if ( is_null($value) ) return \PDO::PARAM_NULL;

		return \PDO::PARAM_STR;
	}

	/**
	 * Error
	 * 
	 * @return string
	 */
	protected function error() : string
	{ 
		return $this->error_msg;
	}

	/**
	 * Number of affected rows
	 * 
	 * @return int
	 */
	public function affectedRows() : int { return $this->num_rows; }

	/**
	 * Get the last error code
	 * 
	 * @return int
	 */
	public function errno() : int { return $this->error_code; }

	public function dispatch(string $event) : void
	{
		$this->profile = DB::fetchAll(DB::query('SHOW PROFILE', [], ['fetch' => \PDO::FETCH_ASSOC]));
		
		if ($this->cumulative_time_listeners)
			foreach ($this->cumulative_time_listeners[$event] as $listener) {
				$listener(new Query($this->getSQL(), $this->options, $this->profile));
			}
	}
}