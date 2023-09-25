<?php
namespace Clicalmani\Flesco\Database;

/**
 * |------------------------------------------------------------
 * |             ***** DBQuery Class *****
 * |------------------------------------------------------------
 * 
 * Database query builder initialiser
 * 
 * This class prepare a database query builder depending on a specific SQL command
 */

use Clicalmani\Flesco\Collection\Collection;
use Clicalmani\Flesco\Database\Factory\Create;
use Clicalmani\Flesco\Database\Factory\Drop;
use Clicalmani\Flesco\Database\Factory\Alter;

define('DB_QUERY_SELECT', 0);
define('DB_QUERY_INSERT', 1);
define('DB_QUERY_DELETE', 2);
define('DB_QUERY_UPDATE', 3);
define('DB_QUERY_CREATE', 4);
define('DB_QUERY_DROP_TABLE', 5);
define('DB_QUERY_DROP_TABLE_IF_EXISTS', 6);
define('DB_QUERY_ALTER_TABLE', 7);

class DBQuery extends DB
{
	/**
	 * Query flag
	 * 
	 * Among the following constants: DB_QUERY_SELECT, DB_QUERY_INSERT, DB_QUERY_UPDATE, DB_QUERY_CREATE
	 */
	private $query;

	const SELECT = DB_QUERY_SELECT;
	const INSERT = DB_QUERY_INSERT;
	const DELETE = DB_QUERY_DELETE;
	const UPDATE = DB_QUERY_UPDATE;
	const CREATE = DB_QUERY_CREATE;
	const ALTER  = DB_QUERY_ALTER_TABLE;

	const DROP_TABLE           = DB_QUERY_DROP_TABLE;
	const DROP_TABLE_IF_EXISTS = DB_QUERY_DROP_TABLE_IF_EXISTS;
	
	function __construct($query = null, $params = [], private $options = [])
	{ 
		$this->params = isset($params)? $params: [];
		
		$this->query = $query;
	}
	
	/**
	 * Sets query parameter
	 * 
	 * @param string $param parameter name
	 * @param string $value parameter value
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function set($param, $value) 
	{ 
		if ($param == 'type') {
			$this->query = $value;
			return;
		}

		$this->params[$param] = $value;
		return $this;
	}

	/**
	 * Unset a query parameter
	 * 
	 * @param string $param Parameter name
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function unset($param)
	{
		unset($this->params[$param]);
		return $this;
	}

	/**
	 * Bounds query parameters.
	 * 
	 * @param array $options Bound parameters in the SQL statement being executed. All values are treated as PDO::PARAM_STR.
	 */
	function setOptions($options)
	{
		$this->options = $options;
	}

	/**
	 * Gets query the specified parameter value
	 * 
	 * @param string $param Parameter name
	 * @return string Parameter value, or null on failure.
	 */
	function getParam($param)
	{
		if (isset($this->params[$param])) {
			return $this->params[$param];
		}

		return null;
	}

	/**
	 * Execute a SQL query command
	 * 
	 * @return \Clicalmani\Flesco\Database\DBQueryBuilder Object
	 */
	function exec()
	{ 
		
		$this->query = isset($this->params['query'])? $this->params['query']: $this->query;
		
		switch ($this->query){
			
			case DB_QUERY_SELECT:
				$obj = new Select($this->params, $this->options);
				$obj->query();
				return $obj;
			
			case DB_QUERY_INSERT:
				$obj = new Insert($this->params, $this->options);
				$obj->query();
				return $obj;
				
			case DB_QUERY_DELETE:
				$obj = new Delete($this->params, $this->options);
				$obj->query();
				return $obj;
				
			case DB_QUERY_UPDATE:
				$obj = new Update($this->params, $this->options);
				$obj->query();
				return $obj;

			case DB_QUERY_CREATE:
				$obj = new Create($this->params, $this->options);
				$obj->query();
				return $obj;

			case DB_QUERY_DROP_TABLE:
				$obj = new Drop($this->params, $this->options);
				$obj->query();
				return $obj;

			case DB_QUERY_DROP_TABLE_IF_EXISTS:
				$this->params['exists'] = true;
				$obj = new Drop($this->params, $this->options);
				$obj->query();
				return $obj;

			case DB_QUERY_ALTER_TABLE:
				$obj = new Alter($this->params, $this->options);
				$obj->query();
				return $obj;
		}
	}

	/**
	 * Alias of get
	 * 
	 * @see DBQuery::get() method
	 * @return \Clicalmani\Flesco\Collection\Collection Object
	 */
	function select($fields = '*') 
	{
		return $this->get($fields);
	}

	/**
	 * Performs a delete request.
	 * 
	 * @return \Clicalmani\Flesco\Database\DBQueryBuilder Object
	 */
	function delete()
	{
		$this->query = DB_QUERY_DELETE;
		return $this;
	}

	/**
	 * Perform an update request.
	 * 
	 * @param array $option [optional] New attribute values
	 * @return \Clicalmani\Flesco\Database\DBQueryBuilder Object
	 */
	function update($options = [])
	{
		$this->set('query', DB_QUERY_UPDATE);

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
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function insert($options = [])
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

		$this->set('query', DB_QUERY_INSERT); 
		
		return $this->exec()->status() === 'success';
	}

	function insertOrFaile($options = [])
	{
		$this->params['ignore'] = true;
		return $this->insert($options);
	}

	/**
	 * Specify the query where condition. 
	 * 
	 * @param string $criteria a SQL query where condition
	 * @param string $operator [optional] An optional where condition operator. Default is AND
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function where( ...$args )
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
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function having($criteria)
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
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function orderBy($order_by) 
	{
		$this->params['order_by'] = $order_by;
		return $this;
	}

	/**
	 * Group the query result set by a specified parameter
	 * 
	 * @param string $group_by a SQL query group by statement
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function groupBy($group_by)
	{
		$this->params['group_by'] = $group_by;
		return $this;
	}

	/**
	 * Wheter to return a distinct result set.
	 * 
	 * @param bool $distinct [optional] default true
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function distinct($distinct = true)
	{
		$this->params['distinct'] = $distinct;
		return $this;
	}

	function from(string $fields) : DBQuery
	{
		$this->params['fields'] = $fields;
		return $this;
	}

	/**
	 * Gets a database query result set. An optional comma separated list of request fields can be specified as 
	 * the unique argument.
	 * 
	 * @see DBQuery::select() method
	 * @param string $fields a list of request fields separated by comma.
	 * @return \Clicalmani\Flesco\Collection\Collection Object
	 */
	function get($fields = '*')
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
	 * @return \Clicalmani\Flesco\Collection\Collection Object
	 */
	function all()
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
	 * @param int $offset the starting index to fetch from
	 * @param int $limit The number of result to be returned
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function limit($offset, $limit)
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
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function join($table)
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
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function joinLeft($table, $parent_id, $child_id)
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
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function joinRight($table, $parent_id, $child_id)
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
	 * @return \Clicalmani\Flesco\Database\DBQuery Object
	 */
	function joinInner($table, $parent_id, $child_id)
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
