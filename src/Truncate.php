<?php
namespace Clicalmani\Database;

/**
 * Class Delete
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Truncate extends DBQueryBuilder implements \IteratorAggregate 
{
	public function __construct(
		protected $params = [], 
		protected $options = []
	) 
	{ 
		parent::__construct($params, $options);
		
		$this->sql = 'TRUNCATE TABLE ' . $this->params['table'];
	}
	
	/**
	 * (non-PHPdoc)
	 * @overriden
	 * @return void
	 */
	public function query() : void 
	{
		/** @var \PDOStatement */
	    $statement = DB::query($this->sql, $this->options, $this->params['options']);

		$this->dispatch('query');
    	
		$this->status     = $statement ? true: false;
	    $this->error_code = DB::errno();
	    $this->error_msg  = DB::error();
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