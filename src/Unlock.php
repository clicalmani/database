<?php
namespace Clicalmani\Database;

/**
 * Class Unlock
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Unlock extends DBQueryBuilder implements \IteratorAggregate 
{
	public function __construct(
		protected $params = array(), 
		protected $options = []
	) 
	{ 
		parent::__construct($params, $options);
		
		$this->sql = 'ALTER TABLE ' . $this->db->getPrefix() . $this->params['table'] . ' ENABLE KEYS; ';
        $this->sql .= 'UNLOCK TABLES;';
	}
	
	/**
	 * (non-PHPdoc)
	 * @overriden
	 * @return void
	 */
	public function query() : void
	{
		$success = $this->db->execute($this->sql);
		
		$this->status     = $success;
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
