<?php
namespace Clicalmani\Database;

/**
 * Class Insert
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Replace extends DBQueryBuilder implements \IteratorAggregate 
{
	public function __construct(
		protected $params = array(), 
		protected $options = []
	) 
	{ 
		parent::__construct($params, $options);
		
		$this->sql .= 'REPLACE INTO ' . $this->db->getPrefix() . $this->params['table'];
		
		if (isset($this->params['fields'])) {

			$this->sql .= ' (' . collection()->exchange($this->params['fields'])->map(function($value) {
				return "`$value`";
			})->join(',') . ') ';
			
			$this->sql .= 'VALUES (' . collection()->exchange($this->params['fields'])->map(function($value) {
				return ":$value";
			})->join(',') . ')';
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @overriden
	 * @return void
	 */
	public function query() : void
	{
		$this->bindVars();
		/** @var \PDOStatement */
		$statement = $this->db->prepare($this->sql, $this->params['options']);
		
		foreach ($this->params['values'] as $row) {
			foreach ($row as $i => $type) {
				if ( is_subclass_of($type, \Clicalmani\Database\Factory\DataTypes\DataType::class) ) $value = $type->getValue();
				else $value = $type;
				$statement->bindValue($this->params['fields'][$i], $value, $this->getDataType($type));
			}
			
			$statement->execute();
		}
		
		$this->status     = $statement ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();
		$this->insert_id  = $this->db->insertId();

		$statement = null;
	}
	
	/**
	 * Get iterator
	 * 
	 * @return \Traversable
	 */
	public function getIterator() : \Traversable
	{
		return new DBQueryIterator($this);
	}
}
