<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\DBQueryIterator;

/**
 * Class Drop
 * 
 * Drop table
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Drop extends DBQueryBuilder implements \IteratorAggregate 
{
	public function __construct(
		protected $params = array(), 
		protected $options = []
	) 
    { 
		parent::__construct($params, $options);
		$this->sql .= 'DROP TABLE ' . (isset($this->params['exists']) ? 'IF EXISTS ': '') . $this->db->getPrefix() . $this->params['table'];
	}

	/**
	 * (non-PHPdoc)
	 * @override
	 * @see \Clicalmani\Database\DBQueryBuilder::query()
	 * @return void
	 */
	public function query() : void
	{
	    $result = $this->db->query($this->sql);
    		
		$this->status     = $result ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();
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
