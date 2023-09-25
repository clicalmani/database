<?php
namespace Clicalmani\Flesco\Database;

/**
 * |-----------------------------------------------------------------
 * |              ***** DBQueryBuilder Class *****
 * |-----------------------------------------------------------------
 * 
 * Database Query Builder
 * 
 * Builds a SQL query to be executed in a database statement.
 * 
 * @package Flesco\Database
 * @author @clicalmani
 */

use Clicalmani\Flesco\Collection\Collection;

abstract class DBQueryBuilder 
{
	
	protected $sql;
	protected $db;
	protected $range;
	protected $limit;
	protected $error_msg;
	protected $error_code;
	protected $insert_id = 0;
	protected $num_rows = 0;
	protected $status;
	protected $result; 
	protected $key = 0;

	protected const JOIN_TYPES = [
		'left'  => 'LEFT JOIN',
		'right' => 'RIGHT JOIN',
		'inner' => 'INNER JOIN'
	];

	public $table;
	
	function __construct(
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
	}
	
	abstract function query();
	
	function execSQL(string $sql) : int|false
	{
		return $this->bd->exec($this->bindVars($sql));
	}
	
	function bindVars($str) 
	{
		$bindings = array(
			'%PREFIX%'=>$this->db->getPrefix(),
			'%APP_KEY%'=>'vie',
			'%APP_CFG%'=>'SECURITE'
		);
		
		foreach ($bindings as $key => $value) {
			
			$str = str_replace($key, $value, $str);
		}
		
		return $str;
	}
	
	function getRow(){ return $this->result[$this->key]; }
	
	function hasResult(){ return $this->num_rows > 0; }
	
	function result(){ return $this->result; }
	
	function numRows(){ return $this->num_rows; }
	
	function status(){ return $this->status ? 'success': 'failure'; }
	
	function insertId(){ return $this->insert_id; }
	
	function key(){ return $this->key; }
	
	function setKey($new_key) { $this->key = $new_key; }
	
	function close() { $this->db->close(); }
	
	function getSQL() { return $this->bindVars($this->sql); }

	function isBoolValue($value)
	{
		return is_bool($value);
	}

	function isNullValue($value) 
	{
		return ($value === 'NULL' || is_null($value));
	}

	function isDefaultValue($value)
	{
		return $value === 'DEFAULT';
	}

	function isExpression($value)
	{
		if (preg_match('/^exp:/i', $value)) return true;

		return false;
	}

	function sanitizeTables(array $tables, bool $prefix = true, bool $alias = true) : array
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

	function addJoint($joint)
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

	function sanitizeValue($value)
	{
		if ($this->isBoolValue($value)) {
			return (int) $value;
		}
		
		// if ($this->isNullValue($value)) {
		// 	return 'NULL';
		// }

		// if ($this->isDefaultValue($value)) {
		// 	return 'DEFAULT';
		// }

		// if ($this->isExpression($value)) {
		// 	return preg_replace('/^exp:/i', '', $value);
		// }

		return $value;
	}

	public function getDataType($data)
	{
		if ( is_int($data) ) return \PDO::PARAM_INT;
		if ( is_bool($data) ) return \PDO::PARAM_BOOL;
		if ( is_null($data) ) return \PDO::PARAM_NULL;

		return \PDO::PARAM_STR;
	}

	function select($raw_sql)
	{
		if ( null != $this->table ) {
			
		}
	}
	
	protected function error(){ 
		if ($this->error_code > 0)
		 echo '';
		else echo "";
	}
}
