<?php
namespace Clicalmani\Database;

class Insert extends DBQueryBuilder implements \IteratorAggregate 
{
	public function __construct(
		protected $params = array(), 
		protected $options = []
	) 
	{ 
		parent::__construct($params, $options);
		
		$this->sql .= 'INSERT ' . ((isset($params['ignore']) AND $params['ignore'] == true) ? 'IGNORE': '') . ' INTO ' . $this->db->getPrefix() . $this->params['table'];
		
		if (isset($this->params['fields'])) {

			$this->sql .= ' (' . collection()->exchange($this->params['fields'])->map(function($value) {
				return "`$value`";
			})->join(',') . ') ';
			
			$this->sql .= 'VALUES (' . collection()->exchange($this->params['fields'])->map(function($value) {
				return ":$value";
			})->join(',') . ')';
		}
	}
	
	public function query() : void
	{
		$this->bindVars();
		$statement = $this->db->prepare($this->sql, $this->params['options']);

		foreach ($this->params['values'] as $row) {
			foreach ($row as $i => $value) {
				$statement->bindValue($this->params['fields'][$i], $value, $this->getDataType($value));
			}

			$statement->execute();
		}
		
		$this->status     = $statement ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();
		$this->insert_id  = $this->db->insertId();

		$statement = null;
	}
	
	function getIterator() : \Traversable
	{
		return new DBQueryIterator($this);
	}
}
