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

		$this->sql = '';
		
		if ( isset($this->params['enable_keys']) ) $this->sql = 'ALTER TABLE ' . DB::getPrefix() . $this->params['table'] . ' ENABLE KEYS; ';
		
        $this->sql .= 'UNLOCK TABLES;';
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
