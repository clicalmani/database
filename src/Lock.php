<?php
namespace Clicalmani\Database;

/**
 * Class Lock
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Lock extends DBQueryBuilder implements \IteratorAggregate 
{
	public function __construct(
		protected $params = array(), 
		protected $options = []
	) 
	{ 
		parent::__construct($params, $options);

		$lock_type = $this->params['lock_type'] ?? 'WRITE';
		
		$this->sql = 'LOCK TABLES ' . DB::getPrefix() . $this->params['table'] . " $lock_type; ";
		
		if ( isset($this->params['disable_keys']) ) {
			$this->sql .= 'ALTER TABLE ' . DB::getPrefix() . $this->params['table'] . ' DISABLE KEYS; ';
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @overriden
	 * @return void
	 */
	public function query() : void
	{
		$success = DB::execute($this->sql);

		$this->dispatch('query');
		
		$this->status     = $success;
	    $this->error_code = DB::errno();
	    $this->error_msg  = DB::error();
		$this->insert_id  = DB::insertId();

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
