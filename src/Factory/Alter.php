<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\DBQueryIterator;

/**
 * Class Alter
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Alter extends DBQueryBuilder implements \IteratorAggregate 
{
	/**
	 * Query parameters
	 * 
	 * @var ?array
	 */
	protected $params = [];

	/**
	 * Query options
	 * 
	 * @var ?array
	 */
	protected $options = [];

	public function __construct($params = [], $options = []) 
    {
		$this->params = $params;
		$this->options = $options;
		parent::__construct($params, $options);
		
		$this->sql .= 'ALTER TABLE ' . $this->db->getPrefix() . $this->params['table'];
		
		if (isset($this->params['definition'])) {
            $this->sql .= ' ' . join(',', $this->params['definition']) . ' ';
		}
	}

	/**
	 * (non-PHPdoc)
	 * @override
	 * @see \Clicalmani\Database\DBQueryBuilder::query()
	 */
	public function query() : void
	{
	    $result = $this->db->execute($this->sql);
    		
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
