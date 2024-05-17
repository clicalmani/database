<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\DBQueryIterator;

/**
 * Class Create
 * 
 * Database table creation
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Create extends DBQueryBuilder implements \IteratorAggregate 
{
	public function __construct(
		protected $params = [], 
		protected $options = []
	) 
    { 
		parent::__construct($params, $options);
		
		$this->sql .= 'CREATE TABLE IF NOT EXISTS `' . $this->db->getPrefix() . $this->params['table'] . '`';
		
		if (isset($this->params['definition'])) {
            $this->sql .= " (\n\t" . join(",\n\t", $this->params['definition']) . ')';
		}
		
		if (isset($this->params['engine'])) $this->sql .= ' ENGINE = ' . $this->params['engine'];

        if (isset($this->params['collate'])) $this->sql .= ' DEFAULT COLLATE = ' . $this->params['collate'];

        if (isset($this->params['charset'])) $this->sql .= ' DEFAULT CHARACTER SET = ' . $this->params['charset'];
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
