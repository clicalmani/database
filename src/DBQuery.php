<?php
namespace Clicalmani\Database;

use Clicalmani\Collection\Collection;
use Clicalmani\Database\Factory\Create;
use Clicalmani\Database\Factory\Drop;
use Clicalmani\Database\Factory\Alter;

/**
 * Database query
 * 
 * Generate the SQL statement to be executed for the requested query.
 * 
 * @package Clicalmani\Database
 * @author clicalmani
 */
class DBQuery extends DB
{
	/**
	 * Query builder parameters
	 * 
	 * @var array $params
	 */
	public $params;

	/**
	 * Select flag
	 * 
	 * @var int 0
	 */
	const SELECT = 0;

	/**
	 * Insert flag
	 * 
	 * @var int 1
	 */
	const INSERT = 1;

	/**
	 * Delete flag
	 * 
	 * @var int 2
	 */
	const DELETE = 2;

	/**
	 * Update flag
	 * 
	 * @var int 3
	 */
	const UPDATE = 3;

	/**
	 * Create flag
	 * 
	 * @var int 4
	 */
	const CREATE = 4;

	/**
	 * Alter flag
	 * 
	 * @var int 7
	 */
	const ALTER  = 7;

	/**
	 * Drop table flag
	 * 
	 * @var int 5
	 */
	const DROP_TABLE = 5;

	/**
	 * Dropt table if exists flag
	 * 
	 * @var int 6
	 */
	const DROP_TABLE_IF_EXISTS = 6;
	
	/**
	 * Constructor
	 * 
	 * @param int|null $query [Optional] DBQuery flag
	 * @param array $params [Optional] Query parameters
	 * @param array $options [Optional] 
	 */
	public function __construct(private int|null $query = null, array $params = [], private array $options = [])
	{ 
		$this->params = isset($params)? $params: [];
		$this->query = $query;
	}
	
	/**
	 * Sets query parameter
	 * 
	 * @param string $param parameter name
	 * @param mixed $value parameter value
	 * @return static
	 */
	public function set(string $param, mixed $value) : static
	{ 
		if ($param == 'type') {
			$this->query = $value;
		} else $this->params[$param] = $value;

		return $this;
	}

	/**
	 * Unset a query parameter
	 * 
	 * @param string $param Parameter name
	 * @return static
	 */
	public function unset(string $param) : static
	{
		unset($this->params[$param]);
		return $this;
	}

	/**
	 * Binds query parameters.
	 * 
	 * @param array $options 
	 * @return void
	 */
	public function setOptions(array $options) : void
	{
		$this->options = $options;
	}

	/**
	 * Gets query the specified parameter value
	 * 
	 * @param string $param Parameter name
	 * @return mixed Parameter value, or null on failure.
	 */
	public function getParam(string $param)
	{
		if (isset($this->params[$param])) {
			return $this->params[$param];
		}

		return null;
	}

	/**
	 * Execute a SQL query command
	 * 
	 * @return mixed
	 */
	public function exec() : mixed
	{ 
		$this->query = isset($this->params['query'])? $this->params['query']: $this->query;
		
		switch ($this->query){
			
			case static::SELECT:
				$obj = new Select($this->params, $this->options);
				$obj->query();
				return $obj;
			
			case static::INSERT:
				$obj = new Insert($this->params, $this->options);
				$obj->query();
				return $obj;
				
			case static::DELETE:
				$obj = new Delete($this->params, $this->options);
				$obj->query();
				return $obj;
				
			case static::UPDATE:
				$obj = new Update($this->params, $this->options);
				$obj->query();
				return $obj;

			case static::CREATE:
				$obj = new Create($this->params, $this->options);
				$obj->query();
				return $obj;

			case static::DROP_TABLE:
				$obj = new Drop($this->params, $this->options);
				$obj->query();
				return $obj;

			case static::DROP_TABLE_IF_EXISTS:
				$this->params['exists'] = true;
				$obj = new Drop($this->params, $this->options);
				$obj->query();
				return $obj;

			case static::ALTER:
				$obj = new Alter($this->params, $this->options);
				$obj->query();
				return $obj;
		}

		return null;
	}

	/**
	 * Alias of get
	 * 
	 * @see DBQuery::get() method
	 * @return \Clicalmani\Collection\Collection
	 */
	public function select(string $fields = '*') : Collection
	{
		return $this->get($fields);
	}

	/**
	 * Performs a delete request.
	 * 
	 * @return static
	 */
	public function delete() : static
	{
		$this->query = static::DELETE;
		return $this;
	}

	/**
	 * Perform an update request.
	 * 
	 * @param array $option [optional] New attribute values
	 * @return bool true on success, false on failure
	 */
	public function update(array $options = []) : bool
	{
		$this->set('query', DBQuery::UPDATE);

		$fields = array_keys( $options );
		$values = array_values( $options );
		
		$this->params['fields'] = $fields;
		$this->params['values'] = $values;
		
		return $this->exec()->status() === 'success';
	}

	/**
	 * Insert new record to the selected database table. 
	 * 
	 * @param array $options [optional] New values to be inserted.
	 * @return bool true on success, false on failure
	 */
	public function insert(array $options = []) : bool
	{
		$table = @ isset( $this->params['tables'][0] ) ? $this->params['tables'][0]: null;

		if ( isset( $table ) ) {
			unset($this->params['tables']);
			$this->params['table'] = $table;
		}

		$this->params['values'] = [];

		foreach ($options as $option) {
			$fields = array_keys( $option );
			$values = array_values( $option );
			
			$this->params['fields']   = $fields;
			$this->params['values'][] = $values;
		}

		$this->set('query', self::INSERT); 
		
		return $this->exec()->status() === 'success';
	}

	/**
	 * Insert new record to the selected table or fail.
	 * 
	 * @return bool true on success, false on failure
	 */
	public function insertOrFail(array $options = []) : bool
	{
		try {
			return $this->insert($options);
		} catch (\PDOException $e) {
			return false;
		}
	}

	/**
	 * Insert new record or update the existing one
	 * 
	 * @param array $options
	 * @return void
	 */
	public function insertOrUpdate(array $options) : void
	{
		if (false === $this->insertOrFail($options)) {

			// Reset table for update
			$this->params['tables'] = [$this->params['table']];

			foreach ($options as $option) $this->update($option);
		}
	}

	/**
	 * Specify the query where condition. 
	 * 
	 * @param array ...$args Takes one, two or three parameters
	 * @return static
	 */
	public function where( ...$args ) : static
	{
		switch(count($args)) {
			case 1:
				$criteria = $args[0];
				$operator = 'AND';
				$options  = [];
			break;

			case 2:
				$criteria = $args[0];
				$operator = $args[1];
				$options  = [];
			break;

			case 3:
				$criteria = $args[0];
				$operator = $args[1];
				$options  = $args[2];
			break;

			default: return $this;
		}
		
		$this->options = $options;

		if ( !isset($this->params['where']) ) {
			$this->params['where'] = $criteria;
		} else {
			$this->params['where'] .= " $operator " . $criteria;
		}
		
		return $this;
	}

	/**
	 * Specify the query having condition
	 * 
	 * @param string $criteria a SQL query having condition
	 * @return static
	 */
	public function having(string $criteria) : static
	{
		if ( !isset($this->params['having']) ) {
			$this->params['having'] = $criteria;
		} else {
			$this->params['having'] .= ' AND ' . $criteria;
		}
		
		return $this;
	}

	/**
	 * Orders the query result set.
	 * 
	 * @param string $order_by a SQL query order by statement
	 * @return static
	 */
	public function orderBy(string $order_by) : static
	{
		$this->params['order_by'] = $order_by;
		return $this;
	}

	/**
	 * Group the query result set by a specified parameter
	 * 
	 * @param string $group_by a SQL query group by statement
	 * @return static
	 */
	public function groupBy(string $group_by) : static
	{
		$this->params['group_by'] = $group_by;
		return $this;
	}

	/**
	 * Wheter to return a distinct result set.
	 * 
	 * @param bool $distinct [optional] default true
	 * @return static
	 */
	public function distinct($distinct = true) : static
	{
		$this->params['distinct'] = $distinct;
		return $this;
	}

	/**
	 * SQL from statement when deleting from joined tables.
	 * 
	 * @param string $fields 
	 * @return static
	 */
	public function from(string $fields) : static
	{
		$this->params['fields'] = $fields;
		return $this;
	}

	/**
	 * Gets a database query result set. An optional comma separated list of request fields can be specified as 
	 * the unique argument.
	 * 
	 * @see Clicalmani\Database\DBQuery::select() method
	 * @param string $fields a list of request fields separated by comma.
	 * @return \Clicalmani\Collection\Collection
	 */
	public function get(string $fields = '*') : Collection
	{
		$this->params['fields'] = $fields;
		$result = $this->exec();
		$collection = new Collection;
		
		foreach ($result as $row) {
			$collection->add($row);
		}

		return $collection;
	}

	/**
	 * Fetch all rows in a query result set.
	 * 
	 * @return \Clicalmani\Collection\Collection
	 */
	public function all() : Collection
	{
		$this->params['where'] = 'TRUE';
		$result = $this->exec();
		$collection = new Collection;
		
		foreach ($result as $row) {
			$collection->add($row);
		}

		return $collection;
	}

	/**
	 * Limit the number of rows to be returned in a query result set.
	 * 
	 * @param int $offset [Optional] the starting index to fetch from. Default is 0
	 * @param int $limit [Optional] The number of result to be returned. Default is 1
	 * @return static
	 */
	public function limit(int $offset = 0, int $limit = 1) : static
	{
		$this->params['calc'] = true;
		$this->params['offset'] = $offset;
		$this->params['limit'] = $limit;
		
		return $this;
	}

	/**
	 * Joins a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @return static
	 */
	public function join(string $table) : static
	{
		$this->params['tables'][] = $table;
		return $this;
	}

	/**
	 * Left join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param string $parent_id Parent key
	 * @param string $child_id Foreign key
	 * @return static
	 */
	public function joinLeft(string $table, string $parent_id, string $child_id) : static
	{
		$joint = [
			'table'    => $table,
			'type'     => 'LEFT',
			'criteria' => ($parent_id == $child_id) ? 'USING(' . $parent_id . ')': 'ON(' . $parent_id . '=' . $child_id . ')'
		];
		
		if ( isset($this->params['join']) AND is_array($this->params['join'])) {
			$this->params['join'][] = $joint;
		} else {
			$this->params['join'] = [];
			$this->params['join'][] = $joint;
		}

		return $this;
	}

	/**
	 * Right join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param string $parent_id Parent key
	 * @param string $child_id Foreign key
	 * @return static
	 */
	public function joinRight(string $table, string $parent_id, string $child_id) : static
	{
		$joint = [
			'table'    => $table,
			'type'     => 'RIGHT',
			'criteria' => ($parent_id == $child_id) ? 'USING(' . $parent_id . ')': 'ON(' . $parent_id . '=' . $child_id . ')'
		];

		if ( isset($this->params['join']) AND is_array($this->params['join'])) {
			$this->params['join'][] = $joint;
		} else {
			$this->params['join'] = [];
			$this->params['join'][] = $joint;
		}

		return $this;
	}

	/**
	 * Inner join a database table to the current selected table. 
	 * 
	 * @param string $table Table name
	 * @param string $parent_id Parent key
	 * @param string $child_id Foreign key
	 * @return static
	 */
	public function joinInner(string $table, string $parent_id, string $child_id) : static
	{
		$joint = [
			'table'    => $table,
			'type'     => 'INNER',
			'criteria' => ($parent_id == $child_id) ? 'USING(' . $parent_id . ')': 'ON(' . $parent_id . '=' . $child_id . ')'
		];

		if ( isset($this->params['join']) AND is_array($this->params['join'])) {
			$this->params['join'][] = $joint;
		} else {
			$this->params['join'] = [];
			$this->params['join'][] = $joint;
		}

		return $this;
	}
}
